<?php

namespace App\Controllers;

use App\Helpers\View;
use App\Helpers\Session;
use App\Helpers\Auth;
use App\Models\RolePermissionModel;

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
        $data['authUser']     = Auth::user();
        $data['flashSuccess'] = Session::getFlash('success');
        $data['flashError']   = Session::getFlash('error');
        $data['flashWarning'] = Session::getFlash('warning');
        // Pass nav modules so the sidebar can hide inaccessible sections
        $role = Auth::role() ?? '';
        $data['navModules']   = RolePermissionModel::modulesForRole($role);
        $this->view->render($template, $data);
    }

    protected function renderNoLayout(string $template, array $data = []): void
    {
        $this->view->setLayout('none');
        $this->render($template, $data);
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
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
}
