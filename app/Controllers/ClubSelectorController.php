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

        $this->render('auth/club_select', [
            'clubs' => $clubs,
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

        $club = reset($match);
        Auth::setClub($clubId, $club['role']);
        Session::remove('pending_clubs');

        $this->redirect('dashboard');
    }
}
