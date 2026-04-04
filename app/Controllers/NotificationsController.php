<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Mailer;
use App\Helpers\Session;
use App\Models\EmailQueueModel;
use App\Models\SettingModel;

class NotificationsController extends BaseController
{
    private EmailQueueModel $queueModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'zarzad']);
        $this->queueModel = new EmailQueueModel();
    }

    /**
     * POST /config/notifications/populate/:type
     * Generates notification emails of the specified type into the queue.
     */
    public function populate(string $type): void
    {
        Csrf::verify();
        $settings = new SettingModel();

        $count = match($type) {
            'competition' => $this->queueModel->queueCompetitionReminders(
                (int)($settings->get('notify_competition_days', 7))
            ),
            'payment'  => $this->queueModel->queuePaymentReminders(),
            'license'  => $this->queueModel->queueLicenseReminders(
                (int)($settings->get('notify_license_days', 30))
            ),
            'medical'  => $this->queueModel->queueMedicalReminders(
                (int)($settings->get('notify_medical_days', 30))
            ),
            default    => 0,
        };

        if ($count > 0) {
            Session::flash('success', "Dodano {$count} wiadomości do kolejki.");
        } else {
            Session::flash('warning', 'Brak nowych wiadomości do kolejkowania (wszystkie już w kolejce lub brak odbiorców).');
        }

        $this->redirect('config/notifications');
    }

    /**
     * POST /config/notifications/send
     * Sends up to 20 pending emails from the queue.
     */
    public function send(): void
    {
        Csrf::verify();
        $settings  = new SettingModel();
        $fromEmail = $settings->get('mail_from_email', '');
        $fromName  = $settings->get('mail_from_name', '');

        if (empty($fromEmail)) {
            Session::flash('error', 'Skonfiguruj adres e-mail nadawcy w ustawieniach powiadomień.');
            $this->redirect('config/notifications');
        }

        $pending = $this->queueModel->getPending(20);
        $sent    = 0;
        $failed  = 0;

        foreach ($pending as $item) {
            $ok = Mailer::send(
                $item['to_email'],
                $item['to_name'],
                $item['subject'],
                $item['body_html'],
                $fromEmail,
                $fromName
            );

            if ($ok) {
                $this->queueModel->markSent($item['id']);
                $sent++;
            } else {
                $this->queueModel->markFailed($item['id'], 'mail() zwrócił false');
                $failed++;
            }
        }

        if ($sent > 0 || $failed > 0) {
            $msg = "Wysłano: {$sent}";
            if ($failed > 0) $msg .= ", błędów: {$failed}";
            Session::flash($failed > 0 ? 'warning' : 'success', $msg);
        } else {
            Session::flash('info', 'Brak oczekujących wiadomości.');
        }

        $this->redirect('config/notifications');
    }

    /**
     * POST /config/notifications/clear-sent
     * Removes all sent emails from the queue.
     */
    public function clearSent(): void
    {
        Csrf::verify();
        $this->queueModel->clearSent();
        Session::flash('success', 'Wysłane wiadomości usunięte z kolejki.');
        $this->redirect('config/notifications');
    }
}
