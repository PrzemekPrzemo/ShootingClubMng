<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\MemberAuth;
use App\Helpers\Session;
use App\Models\MemberModel;

/**
 * Serves member photos from storage/photos/.
 * Accessible to both admin sessions (Auth) and member portal sessions (MemberAuth).
 * No requireLogin() in constructor — auth is checked per method.
 */
class PhotoController
{
    public function __construct()
    {
        Session::start();
    }

    public function serve(string $id): void
    {
        if (!Auth::check() && !MemberAuth::check()) {
            http_response_code(403);
            exit;
        }

        $model  = new MemberModel();
        $member = $model->findById((int)$id);

        if (!$member || empty($member['photo_path'])) {
            http_response_code(404);
            exit;
        }

        $fullPath = ROOT_PATH . '/storage/photos/' . $member['photo_path'];
        if (!file_exists($fullPath)) {
            http_response_code(404);
            exit;
        }

        $ext  = strtolower(pathinfo($member['photo_path'], PATHINFO_EXTENSION));
        $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: private, max-age=86400');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($fullPath)) . ' GMT');
        readfile($fullPath);
        exit;
    }
}
