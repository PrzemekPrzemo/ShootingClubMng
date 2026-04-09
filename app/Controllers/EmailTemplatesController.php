<?php

namespace App\Controllers;

use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;

/**
 * Zarządzanie szablonami e-mail per klub.
 *
 * GET  /club/email-templates            — lista szablonów
 * GET  /club/email-templates/:type/edit — edycja szablonu
 * POST /club/email-templates/:type      — zapisz szablon
 * POST /club/email-templates/:type/reset — przywróć domyślny
 */
class EmailTemplatesController extends BaseController
{
    private static array $TEMPLATE_TYPES = [
        'competition_reminder' => 'Przypomnienie o zawodach',
        'license_expiry'       => 'Wygasająca licencja PZSS',
        'payment_reminder'     => 'Zaległe składki',
        'medical_reminder'     => 'Wygasające badanie lekarskie',
        'welcome'              => 'Powitanie nowego zawodnika',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireRole(['admin', 'zarzad']);
        $this->requireClubContext();
    }

    public function index(): void
    {
        $clubId    = ClubContext::current();
        $db        = Database::pdo();
        $templates = [];

        // Load templates: per-club overrides global
        foreach (self::$TEMPLATE_TYPES as $type => $label) {
            $stmt = $db->prepare(
                "SELECT *, (club_id IS NOT NULL) AS is_custom
                 FROM email_templates
                 WHERE template_type = ? AND (club_id = ? OR club_id IS NULL)
                 ORDER BY club_id DESC
                 LIMIT 1"
            );
            $stmt->execute([$type, $clubId]);
            $tpl = $stmt->fetch();

            $templates[$type] = [
                'type'      => $type,
                'label'     => $label,
                'subject'   => $tpl['subject'] ?? '(brak szablonu)',
                'is_custom' => !empty($tpl['is_custom']),
                'is_active' => !empty($tpl['is_active']),
            ];
        }

        $this->render('email_templates/index', [
            'title'     => 'Szablony e-mail',
            'templates' => $templates,
        ]);
    }

    public function edit(string $type): void
    {
        $this->validateType($type);
        $clubId = ClubContext::current();
        $db     = Database::pdo();

        // Load per-club template if exists, otherwise global default
        $stmt = $db->prepare(
            "SELECT * FROM email_templates
             WHERE template_type = ? AND (club_id = ? OR club_id IS NULL)
             ORDER BY club_id DESC
             LIMIT 1"
        );
        $stmt->execute([$type, $clubId]);
        $template = $stmt->fetch() ?: ['subject' => '', 'body_html' => '', 'variables_hint' => ''];

        $this->render('email_templates/edit', [
            'title'    => 'Edytuj szablon: ' . (self::$TEMPLATE_TYPES[$type] ?? $type),
            'type'     => $type,
            'label'    => self::$TEMPLATE_TYPES[$type] ?? $type,
            'template' => $template,
        ]);
    }

    public function save(string $type): void
    {
        Csrf::verify();
        $this->validateType($type);

        $clubId  = ClubContext::current();
        $db      = Database::pdo();
        $subject = trim($_POST['subject'] ?? '');
        $body    = $_POST['body_html'] ?? '';

        if (empty($subject)) {
            Session::flash('error', 'Temat wiadomości jest wymagany.');
            $this->redirect('club/email-templates/' . $type . '/edit');
        }

        // Upsert per-club template
        $existing = $db->prepare(
            "SELECT id FROM email_templates WHERE template_type = ? AND club_id = ? LIMIT 1"
        );
        $existing->execute([$type, $clubId]);
        $row = $existing->fetch();

        if ($row) {
            $db->prepare(
                "UPDATE email_templates SET subject = ?, body_html = ?, updated_at = NOW() WHERE id = ?"
            )->execute([$subject, $body, $row['id']]);
        } else {
            $db->prepare(
                "INSERT INTO email_templates (club_id, template_type, subject, body_html, is_active) VALUES (?, ?, ?, ?, 1)"
            )->execute([$clubId, $type, $subject, $body]);
        }

        Session::flash('success', 'Szablon e-mail zapisany.');
        $this->redirect('club/email-templates');
    }

    public function reset(string $type): void
    {
        Csrf::verify();
        $this->validateType($type);

        $clubId = ClubContext::current();
        $db     = Database::pdo();

        // Delete per-club override — falls back to global default
        $db->prepare(
            "DELETE FROM email_templates WHERE template_type = ? AND club_id = ?"
        )->execute([$type, $clubId]);

        Session::flash('success', 'Szablon przywrócony do domyślnego.');
        $this->redirect('club/email-templates');
    }

    private function validateType(string $type): void
    {
        if (!array_key_exists($type, self::$TEMPLATE_TYPES)) {
            Session::flash('error', 'Nieznany typ szablonu.');
            $this->redirect('club/email-templates');
        }
    }
}
