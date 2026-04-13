<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;

class ClubSelectorController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->view->setLayout('auth');
    }

    /** Pokaż listę klubów do wyboru (po zalogowaniu, gdy user ma wiele klubów). */
    public function show(): void
    {
        if (!Auth::check()) {
            $this->redirect('auth/login');
        }

        $clubs = Session::get('pending_clubs');
        if (!$clubs || !is_array($clubs) || count($clubs) === 0) {
            $this->redirect('dashboard');
        }

        // Load system branding for the view
        $systemBranding = ['name' => 'Shootero', 'logo' => '', 'logoMts' => '0'];
        try {
            $sm       = new \App\Models\SettingModel();
            $logoFile = (string)($sm->get('system_logo', '') ?: '');
            $logoPath = ROOT_PATH . '/storage/system/' . basename($logoFile);
            $logoOk   = $logoFile !== '' && file_exists($logoPath);
            $systemBranding['name']    = $sm->get('system_name', 'Shootero') ?: 'Shootero';
            $systemBranding['logo']    = $logoOk ? $logoFile : '';
            $systemBranding['logoMts'] = $logoOk ? (string)filemtime($logoPath) : '0';
        } catch (\Throwable) {}

        $this->render('auth/club_select', [
            'title'          => 'Wybierz klub',
            'clubs'          => $clubs,
            'systemBranding' => $systemBranding,
        ]);
    }

    /** POST — wybierz klub i ustaw kontekst. */
    public function select(string $clubId): void
    {
        Csrf::verify();

        if (!Auth::check()) {
            $this->redirect('auth/login');
        }

        $clubId = (int)$clubId;
        $clubs  = Session::get('pending_clubs', []);

        // Znajdź wybrany klub w liście dozwolonych
        $match = array_filter($clubs, fn($c) => (int)$c['club_id'] === $clubId);

        if (!$match) {
            Session::flash('error', 'Nie masz dostępu do tego klubu.');
            $this->redirect('club-select');
        }

        $club  = reset($match);
        $roles = $club['roles'] ?? [$club['role']];
        Session::remove('pending_clubs');

        // Multiple roles in this club → role selection screen
        if (count($roles) > 1) {
            Session::set('pending_role_select', [
                'user_id' => Auth::id(),
                'club_id' => $clubId,
                'roles'   => $roles,
            ]);
            $this->redirect('auth/role-select');
        }

        Auth::setClub($clubId, $roles[0]);
        $this->redirect('dashboard');
    }
}
