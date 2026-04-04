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

// App config (app.local.php overrides app.php when present)
$localApp  = ROOT_PATH . '/config/app.local.php';
$appConfig = file_exists($localApp)
    ? require $localApp
    : require ROOT_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

// ── Obsługa błędów ────────────────────────────────────────────────────────────
$debugMode = (bool)($appConfig['debug'] ?? false);

if ($debugMode) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);   // zbieraj do loga, nie wyświetlaj
}

// Log błędów aplikacji
$logDir = ROOT_PATH . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
ini_set('log_errors', '1');
ini_set('error_log', $logDir . '/app.log');

// Globalny handler wyjątków — czytelna strona błędu zamiast białego ekranu
set_exception_handler(function (Throwable $e) use ($debugMode, $logDir): void {
    http_response_code(500);

    // Zawsze loguj
    $msg = sprintf(
        "[%s] %s: %s in %s:%d\nStack trace:\n%s\n",
        date('Y-m-d H:i:s'),
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    error_log($msg);

    if ($debugMode) {
        // Tryb debug — pełny opis
        echo '<!DOCTYPE html><html lang="pl"><head><meta charset="UTF-8">'
            . '<title>Błąd aplikacji</title>'
            . '<style>body{font-family:monospace;background:#1e1e2e;color:#cdd6f4;padding:2em}'
            . 'h1{color:#f38ba8} pre{background:#313244;padding:1em;border-radius:8px;overflow-x:auto}'
            . '.badge{display:inline-block;padding:2px 8px;background:#f38ba8;color:#1e1e2e;border-radius:4px}</style></head><body>'
            . '<h1>&#10060; Błąd aplikacji</h1>'
            . '<p><span class="badge">' . htmlspecialchars(get_class($e)) . '</span></p>'
            . '<p><strong>' . htmlspecialchars($e->getMessage()) . '</strong></p>'
            . '<p style="color:#a6adc8">Plik: ' . htmlspecialchars($e->getFile()) . ' : ' . $e->getLine() . '</p>'
            . '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>'
            . '<hr><p style="color:#585b70;font-size:12px">Tryb debug włączony — wyłącz na produkcji (config/app.local.php → debug: false)</p>'
            . '</body></html>';
    } else {
        // Tryb produkcyjny — ogólny komunikat
        echo '<!DOCTYPE html><html lang="pl"><head><meta charset="UTF-8">'
            . '<title>Błąd serwera</title>'
            . '<style>body{font-family:sans-serif;background:#f8f9fa;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}'
            . '.box{text-align:center;padding:3em}'
            . 'h1{color:#dc3545;font-size:4em;margin:0}'
            . 'p{color:#6c757d}</style></head><body>'
            . '<div class="box"><h1>&#9888;</h1>'
            . '<h2>Błąd serwera</h2>'
            . '<p>Wystąpił nieoczekiwany błąd. Administrator został powiadomiony.</p>'
            . '<p style="font-size:12px;color:#adb5bd">Błąd zapisany do: logs/app.log</p>'
            . '<a href="' . BASE_URL . '">Wróć na stronę główną</a></div>'
            . '</body></html>';
    }
    exit(1);
});

// Handler błędów PHP (E_ERROR, E_PARSE itp.) — konwertuj na wyjątki
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Fatal errors (parse error, out of memory) — złap przez shutdown
register_shutdown_function(function () use ($debugMode, $logDir): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        $msg = sprintf(
            "[%s] FATAL %s in %s:%d\n",
            date('Y-m-d H:i:s'),
            $error['message'],
            $error['file'],
            $error['line']
        );
        error_log($msg);

        if ($debugMode) {
            echo '<pre style="background:#1e1e2e;color:#f38ba8;padding:1em">'
                . '&#128680; FATAL ERROR&#10;'
                . htmlspecialchars($msg)
                . '</pre>';
        } else {
            echo '<p style="font-family:sans-serif;text-align:center;color:#dc3545">'
                . 'Krytyczny błąd serwera. Sprawdź logs/app.log</p>';
        }
    }
});


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
// Competition events (konkurencje)
$router->get('/competitions/:id/events',                          [\App\Controllers\CompetitionsController::class, 'events']);
$router->post('/competitions/:id/events/add',                     [\App\Controllers\CompetitionsController::class, 'addEvent']);
$router->post('/competitions/:id/events/:eid/delete',             [\App\Controllers\CompetitionsController::class, 'deleteEvent']);
$router->get('/competitions/:id/events/:eid/results',             [\App\Controllers\CompetitionsController::class, 'eventResults']);
$router->post('/competitions/:id/events/:eid/results/save',       [\App\Controllers\CompetitionsController::class, 'saveEventResults']);
$router->get('/competitions/:id/events/:eid/startcard',           [\App\Controllers\CompetitionsController::class, 'startCard']);

// Reports
$router->get('/reports',                [\App\Controllers\ReportsController::class, 'index']);
$router->get('/reports/members',        [\App\Controllers\ReportsController::class, 'members']);
$router->get('/reports/finances',       [\App\Controllers\ReportsController::class, 'finances']);
$router->get('/reports/licenses',       [\App\Controllers\ReportsController::class, 'licenses']);
$router->get('/reports/competitions',   [\App\Controllers\ReportsController::class, 'competitions']);

// Judges
$router->get('/judges',                  [\App\Controllers\JudgesController::class, 'index']);
$router->get('/judges/create',           [\App\Controllers\JudgesController::class, 'create']);
$router->post('/judges/create',          [\App\Controllers\JudgesController::class, 'store']);
$router->get('/judges/:id/edit',         [\App\Controllers\JudgesController::class, 'edit']);
$router->post('/judges/:id/edit',        [\App\Controllers\JudgesController::class, 'update']);
$router->post('/judges/:id/delete',      [\App\Controllers\JudgesController::class, 'destroy']);
$router->post('/judges/:id/fee-paid',    [\App\Controllers\JudgesController::class, 'markFeePaid']);

// Competition Judges
$router->post('/competitions/:id/judges/add',           [\App\Controllers\CompetitionsController::class, 'addJudge']);
$router->post('/competitions/:id/judges/:jid/remove',   [\App\Controllers\CompetitionsController::class, 'removeJudge']);

// Club Fees (PZSS/PomZSS)
$router->get('/club-fees',               [\App\Controllers\ClubFeesController::class, 'index']);
$router->get('/club-fees/:year',         [\App\Controllers\ClubFeesController::class, 'index']);
$router->post('/club-fees/calculate',    [\App\Controllers\ClubFeesController::class, 'calculate']);
$router->post('/club-fees/:id/paid',     [\App\Controllers\ClubFeesController::class, 'markPaid']);

// Medical exam file download
$router->get('/members/:member_id/exams/:id/file', [\App\Controllers\MedicalExamsController::class, 'downloadFile']);

// Configuration
$router->get('/config',                 [\App\Controllers\ConfigController::class, 'index']);
$router->post('/config',                [\App\Controllers\ConfigController::class, 'save']);
$router->get('/config/categories',      [\App\Controllers\ConfigController::class, 'categories']);
$router->post('/config/categories',     [\App\Controllers\ConfigController::class, 'saveCategory']);
$router->post('/config/categories/:id/delete',    [\App\Controllers\ConfigController::class, 'deleteCategory']);
// Disciplines
$router->get('/config/disciplines',               [\App\Controllers\ConfigController::class, 'disciplines']);
$router->post('/config/disciplines',              [\App\Controllers\ConfigController::class, 'saveDiscipline']);
$router->post('/config/disciplines/:id/delete',   [\App\Controllers\ConfigController::class, 'deleteDiscipline']);
$router->post('/config/disciplines/:id/toggle',   [\App\Controllers\ConfigController::class, 'toggleDiscipline']);
// Member classes
$router->get('/config/member-classes',            [\App\Controllers\ConfigController::class, 'memberClasses']);
$router->post('/config/member-classes',           [\App\Controllers\ConfigController::class, 'saveMemberClass']);
$router->post('/config/member-classes/:id/delete',[\App\Controllers\ConfigController::class, 'deleteMemberClass']);
// Medical Exam Types
$router->get('/config/medical-exam-types',              [\App\Controllers\ConfigController::class, 'medicalExamTypes']);
$router->post('/config/medical-exam-types',             [\App\Controllers\ConfigController::class, 'saveMedicalExamType']);
$router->post('/config/medical-exam-types/:id/delete',  [\App\Controllers\ConfigController::class, 'deleteMedicalExamType']);
$router->get('/config/users',           [\App\Controllers\ConfigController::class, 'users']);
$router->get('/config/users/create',    [\App\Controllers\ConfigController::class, 'createUser']);
$router->post('/config/users/create',   [\App\Controllers\ConfigController::class, 'storeUser']);
$router->get('/config/users/:id/edit',  [\App\Controllers\ConfigController::class, 'editUser']);
$router->post('/config/users/:id/edit', [\App\Controllers\ConfigController::class, 'updateUser']);
$router->post('/config/users/:id/delete', [\App\Controllers\ConfigController::class, 'deleteUser']);

// Dispatch
$router->dispatch();
