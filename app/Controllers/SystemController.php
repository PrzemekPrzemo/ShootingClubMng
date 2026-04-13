<?php

namespace App\Controllers;

use App\Models\SettingModel;

/**
 * Public endpoints that require no authentication.
 * (System assets: logo, etc.)
 */
class SystemController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // No auth check — these routes are intentionally public
    }

    /** GET /system-logo — serves system logo (file or base64 from DB), no auth required */
    public function serveLogo(): void
    {
        $settingModel = new SettingModel();
        $fileName     = (string)($settingModel->get('system_logo', '') ?: '');

        // Option A: logo stored as file on disk
        if ($fileName !== '' && $fileName !== 'db') {
            $path = ROOT_PATH . '/storage/system/' . basename($fileName);
            if (file_exists($path)) {
                $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime = ['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg',
                         'svg'=>'image/svg+xml','webp'=>'image/webp'][$ext] ?? 'image/png';
                $mts  = filemtime($path);
                header('Content-Type: ' . $mime);
                header('Cache-Control: public, max-age=604800, immutable');
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mts) . ' GMT');
                header('ETag: "' . $mts . '"');
                readfile($path);
                exit;
            }
        }

        // Option B: logo stored as base64 in database
        if ($fileName === 'db') {
            $b64 = (string)($settingModel->get('system_logo_b64', '') ?: '');
            if (str_starts_with($b64, 'data:')) {
                [$meta, $data] = explode(',', $b64, 2);
                $mime = str_replace(['data:', ';base64'], '', $meta);
                $raw  = base64_decode($data);
                header('Content-Type: ' . $mime);
                header('Content-Length: ' . strlen($raw));
                header('Cache-Control: public, max-age=86400');
                echo $raw;
                exit;
            }
        }

        http_response_code(404);
        exit;
    }
}
