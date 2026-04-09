<?php

namespace App\Controllers;

use App\Helpers\View;
use App\Helpers\Session;
use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Models\RolePermissionModel;
use App\Models\ClubCustomizationModel;

abstract class BaseController
{
    protected View $view;

    public function __construct()
    {
        Session::start();
        $this->view = new View();
    }

    protected function render(string $template, array $data = []): void
    {
        $data['authUser']      = Auth::user();
        $data['flashSuccess']  = Session::getFlash('success');
        $data['flashError']    = Session::getFlash('error');
        $data['flashWarning']  = Session::getFlash('warning');
        // Pass nav modules so the sidebar can hide inaccessible sections
        $role = Auth::role() ?? '';
        $data['navModules']    = RolePermissionModel::modulesForRole($role);
        // Club branding for layout
        $data['clubBranding']  = ClubCustomizationModel::getForCurrentClub();
        $data['isSuperAdmin']  = Auth::isSuperAdmin();
        $this->view->render($template, $data);
    }

    protected function renderNoLayout(string $template, array $data = []): void
    {
        $this->view->setLayout('none');
        $this->render($template, $data);
    }

    /**
     * Renders a view template to a string (no layout). Used for PDF generation.
     */
    protected function renderToString(string $template, array $data = []): string
    {
        $file = ROOT_PATH . '/app/Views/' . $template . '.php';
        ob_start();
        extract($data, EXTR_SKIP);
        include $file;
        return ob_get_clean();
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }

    /**
     * Redirects back to HTTP_REFERER only when it belongs to this application.
     * Falls back to $fallback if referer is absent or points to an external host.
     */
    protected function safeRedirectBack(string $fallback): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer !== '') {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $ownHost     = $_SERVER['HTTP_HOST'] ?? '';
            if ($refererHost !== false && $refererHost === $ownHost) {
                header('Location: ' . $referer);
                exit;
            }
        }
        $this->redirect($fallback);
    }

    protected function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function requireLogin(): void
    {
        Auth::requireLogin();
    }

    protected function requireRole(string|array $roles): void
    {
        Auth::requireRole($roles);
    }

    /**
     * Wymaga aktywnego kontekstu klubu w sesji.
     * Super admin musi najpierw wybrać klub przez /admin/switch-club.
     */
    protected function requireClubContext(): void
    {
        if (ClubContext::current() === null) {
            Session::flash('warning', 'Wybierz klub, aby kontynuować.');
            $this->redirect('club-select');
        }
    }

    /** Zwraca aktywny club_id (skrót do ClubContext::require()). */
    protected function currentClub(): int
    {
        return ClubContext::require();
    }

    /** Wymaga, by user był super adminem. */
    protected function requireSuperAdmin(): void
    {
        $this->requireLogin();
        if (!Auth::isSuperAdmin()) {
            http_response_code(403);
            die('Brak uprawnień — wymagany dostęp super admina.');
        }
    }
}
