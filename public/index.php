<?php
declare(strict_types=1);

// ============================================================
// Front Controller
// ============================================================

define('ROOT_PATH', dirname(__DIR__));

// Auto-detect base URL
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseDir  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
define('BASE_URL', $scheme . '://' . $host . $baseDir);

// App config
$appConfig = require ROOT_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

if ($appConfig['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Autoloader (simple PSR-4 style)
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = ROOT_PATH . '/app/' . $relative . '.php';
    if (file_exists($file)) require $file;
});

// Global helpers
require ROOT_PATH . '/app/Helpers/Helpers.php';

// Session
\App\Helpers\Session::start();

// ============================================================
// Routes
// ============================================================
$router = new \App\Helpers\Router();

// Auth
$router->get('/auth/login',    [\App\Controllers\AuthController::class, 'showLogin']);
$router->post('/auth/login',   [\App\Controllers\AuthController::class, 'login']);
$router->get('/auth/logout',   [\App\Controllers\AuthController::class, 'logout']);

// Dashboard
$router->get('/',              [\App\Controllers\DashboardController::class, 'index']);
$router->get('/dashboard',     [\App\Controllers\DashboardController::class, 'index']);

// Members
$router->get('/members',                [\App\Controllers\MembersController::class, 'index']);
$router->get('/members/create',         [\App\Controllers\MembersController::class, 'create']);
$router->post('/members/create',        [\App\Controllers\MembersController::class, 'store']);
$router->get('/members/:id',            [\App\Controllers\MembersController::class, 'show']);
$router->get('/members/:id/edit',       [\App\Controllers\MembersController::class, 'edit']);
$router->post('/members/:id/edit',      [\App\Controllers\MembersController::class, 'update']);
$router->post('/members/:id/delete',    [\App\Controllers\MembersController::class, 'destroy']);

// Medical Exams
$router->get('/members/:member_id/exams',            [\App\Controllers\MedicalExamsController::class, 'index']);
$router->get('/members/:member_id/exams/create',     [\App\Controllers\MedicalExamsController::class, 'create']);
$router->post('/members/:member_id/exams/create',    [\App\Controllers\MedicalExamsController::class, 'store']);
$router->get('/members/:member_id/exams/:id/edit',   [\App\Controllers\MedicalExamsController::class, 'edit']);
$router->post('/members/:member_id/exams/:id/edit',  [\App\Controllers\MedicalExamsController::class, 'update']);
$router->post('/members/:member_id/exams/:id/delete',[\App\Controllers\MedicalExamsController::class, 'destroy']);

// Licenses
$router->get('/licenses',               [\App\Controllers\LicensesController::class, 'index']);
$router->get('/licenses/create',        [\App\Controllers\LicensesController::class, 'create']);
$router->post('/licenses/create',       [\App\Controllers\LicensesController::class, 'store']);
$router->get('/licenses/:id/edit',      [\App\Controllers\LicensesController::class, 'edit']);
$router->post('/licenses/:id/edit',     [\App\Controllers\LicensesController::class, 'update']);
$router->post('/licenses/:id/delete',   [\App\Controllers\LicensesController::class, 'destroy']);

// Finances
$router->get('/finances',               [\App\Controllers\FinancesController::class, 'index']);
$router->get('/finances/create',        [\App\Controllers\FinancesController::class, 'create']);
$router->post('/finances/create',       [\App\Controllers\FinancesController::class, 'store']);
$router->get('/finances/:id/edit',      [\App\Controllers\FinancesController::class, 'edit']);
$router->post('/finances/:id/edit',     [\App\Controllers\FinancesController::class, 'update']);
$router->post('/finances/:id/delete',   [\App\Controllers\FinancesController::class, 'destroy']);
$router->get('/finances/debts',         [\App\Controllers\FinancesController::class, 'debts']);

// Competitions
$router->get('/competitions',                        [\App\Controllers\CompetitionsController::class, 'index']);
$router->get('/competitions/create',                 [\App\Controllers\CompetitionsController::class, 'create']);
$router->post('/competitions/create',                [\App\Controllers\CompetitionsController::class, 'store']);
$router->get('/competitions/:id',                    [\App\Controllers\CompetitionsController::class, 'show']);
$router->get('/competitions/:id/edit',               [\App\Controllers\CompetitionsController::class, 'edit']);
$router->post('/competitions/:id/edit',              [\App\Controllers\CompetitionsController::class, 'update']);
$router->post('/competitions/:id/delete',            [\App\Controllers\CompetitionsController::class, 'destroy']);
$router->get('/competitions/:id/entries',            [\App\Controllers\CompetitionsController::class, 'entries']);
$router->post('/competitions/:id/entries/add',       [\App\Controllers\CompetitionsController::class, 'addEntry']);
$router->post('/competitions/:id/entries/:eid/remove', [\App\Controllers\CompetitionsController::class, 'removeEntry']);
$router->get('/competitions/:id/results',            [\App\Controllers\CompetitionsController::class, 'results']);
$router->post('/competitions/:id/results/save',      [\App\Controllers\CompetitionsController::class, 'saveResults']);

// Reports
$router->get('/reports',                [\App\Controllers\ReportsController::class, 'index']);
$router->get('/reports/members',        [\App\Controllers\ReportsController::class, 'members']);
$router->get('/reports/finances',       [\App\Controllers\ReportsController::class, 'finances']);
$router->get('/reports/licenses',       [\App\Controllers\ReportsController::class, 'licenses']);
$router->get('/reports/competitions',   [\App\Controllers\ReportsController::class, 'competitions']);

// Configuration
$router->get('/config',                 [\App\Controllers\ConfigController::class, 'index']);
$router->post('/config',                [\App\Controllers\ConfigController::class, 'save']);
$router->get('/config/categories',      [\App\Controllers\ConfigController::class, 'categories']);
$router->post('/config/categories',     [\App\Controllers\ConfigController::class, 'saveCategory']);
$router->post('/config/categories/:id/delete', [\App\Controllers\ConfigController::class, 'deleteCategory']);
$router->get('/config/users',           [\App\Controllers\ConfigController::class, 'users']);
$router->get('/config/users/create',    [\App\Controllers\ConfigController::class, 'createUser']);
$router->post('/config/users/create',   [\App\Controllers\ConfigController::class, 'storeUser']);
$router->get('/config/users/:id/edit',  [\App\Controllers\ConfigController::class, 'editUser']);
$router->post('/config/users/:id/edit', [\App\Controllers\ConfigController::class, 'updateUser']);
$router->post('/config/users/:id/delete', [\App\Controllers\ConfigController::class, 'deleteUser']);

// Dispatch
$router->dispatch();
