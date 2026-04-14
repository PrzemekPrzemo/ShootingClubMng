<?php
// Feature descriptions for display (additional context)
$localDescriptions = [
    'calendar'        => 'Wyświetla miesięczny kalendarz zawodów dostępny dla wszystkich zalogowanych użytkowników.',
    'trainings'       => 'Moduł do zarządzania treningami i listami obecności uczestników. Wymaga migracji v18.',
    'announcements'   => 'Zarządzanie ogłoszeniami klubowymi. Wymaga migracji v18.',
    'audit_log'       => 'Rejestruje wszystkie operacje w systemie (kto, co, kiedy zrobił).',
    'member_card'     => 'Umożliwia drukowanie legitymacji zawodniczej w formacie A7.',
    'stats_dashboard' => 'Zaawansowany panel statystyczny z wykresami dla zarządu.',
    'csv_import'      => 'Import danych zawodników z pliku CSV (funkcja beta, może zawierać błędy).',
    'lane_bookings'   => 'Rezerwacja stanowisk strzeleckich przez zawodników (moduł w budowie). Wymaga migracji v19.',
];
$betaFeatures = ['csv_import', 'lane_bookings'];
?>

<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('config') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-toggles"></i> Flagi funkcji (Feature Flags)</h2>
</div>

<!-- Config nav tabs (same as other config views) -->
<div class="row g-2 mb-4 flex-wrap">
    <?php $uri = $_SERVER['REQUEST_URI']; ?>
    <div class="col-auto">
        <a href="<?= url('config') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-sliders"></i> Ustawienia
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/categories') ?>" class="btn btn-outline-secondary btn-sm <?= str_contains($uri,'/categories') ? 'active':'' ?>">
            <i class="bi bi-tags"></i> Kategorie wiekowe
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/disciplines') ?>" class="btn btn-outline-secondary btn-sm <?= str_contains($uri,'/disciplines') && !str_contains($uri,'/templates') ? 'active':'' ?>">
            <i class="bi bi-bullseye"></i> Dyscypliny
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/member-classes') ?>" class="btn btn-outline-secondary btn-sm <?= str_contains($uri,'/member-classes') ? 'active':'' ?>">
            <i class="bi bi-award"></i> Klasy zawodników
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/users') ?>" class="btn btn-outline-secondary btn-sm <?= str_contains($uri,'/users') ? 'active':'' ?>">
            <i class="bi bi-people"></i> Użytkownicy
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/notifications') ?>" class="btn btn-outline-secondary btn-sm <?= str_contains($uri,'/notifications') ? 'active':'' ?>">
            <i class="bi bi-bell"></i> Powiadomienia
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/features') ?>" class="btn btn-outline-secondary btn-sm active">
            <i class="bi bi-toggles"></i> Moduły
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/audit-log') ?>" class="btn btn-outline-secondary btn-sm <?= str_contains($uri,'/audit') ? 'active':'' ?>">
            <i class="bi bi-journal-text"></i> Dziennik audytu
        </a>
    </div>
</div>

<div class="row">
<div class="col-lg-8">

<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <strong>Zarządzanie modułami systemu</strong>
        <span class="badge bg-secondary ms-auto"><?= count($allFeatures ?? []) ?> modułów</span>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Włącz lub wyłącz poszczególne moduły systemu. Wyłączony moduł jest niewidoczny dla użytkowników
            i przekierowuje do dashboardu przy próbie bezpośredniego dostępu.
        </p>

        <form method="post" action="<?= url('config/features') ?>">
            <?= csrf_field() ?>

            <?php if (!empty($allFeatures)): ?>
            <div class="list-group">
            <?php foreach ($allFeatures as $name => $cfg):
                $settingKey = 'feature_' . $name;
                $currentVal = $settings[$settingKey]['value'] ?? $settings[$settingKey] ?? null;
                // $settings may be a flat [key => row] map or a list — handle both
                if (is_array($currentVal)) {
                    $enabled = (bool)(int)($currentVal['value'] ?? 0);
                } else {
                    $enabled = ($currentVal !== null) ? (bool)(int)$currentVal : true;
                }
                $label = $cfg['label'] ?? ('Moduł: ' . ucfirst($name));
                $desc  = $cfg['desc'] ?? ($localDescriptions[$name] ?? '');
                $isBeta = in_array($name, $betaFeatures);
            ?>
                <div class="list-group-item list-group-item-action d-flex align-items-start gap-3 py-3">
                    <!-- Toggle switch -->
                    <div class="form-check form-switch mt-1 mb-0 flex-shrink-0">
                        <input class="form-check-input" type="checkbox"
                               name="feature[<?= e($name) ?>]"
                               id="feat_<?= e($name) ?>"
                               value="1"
                               role="switch"
                               <?= $enabled ? 'checked' : '' ?>>
                    </div>

                    <!-- Label + description -->
                    <label class="flex-grow-1 mb-0" for="feat_<?= e($name) ?>" style="cursor:pointer">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-semibold"><?= e($label) ?></span>
                            <?php if ($isBeta): ?>
                                <span class="badge bg-warning text-dark" style="font-size:.65rem">BETA</span>
                            <?php endif; ?>
                            <?php if (!$enabled): ?>
                                <span class="badge bg-light text-secondary border" style="font-size:.65rem">WYŁĄCZONY</span>
                            <?php else: ?>
                                <span class="badge bg-success" style="font-size:.65rem">AKTYWNY</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($desc): ?>
                            <div class="text-muted small mt-1"><?= e($desc) ?></div>
                        <?php endif; ?>
                    </label>
                </div>
            <?php endforeach; ?>
            </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    Nie znaleziono definicji flag funkcji.
                    Uruchom migrację <code>migration_v18.sql</code> aby dodać wpisy do tabeli <code>settings</code>.
                </div>
            <?php endif; ?>

            <div class="mt-4">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-check-lg"></i> Zapisz ustawienia modułów
                </button>
                <span class="text-muted small ms-2">Zmiany są widoczne natychmiast po zapisaniu.</span>
            </div>
        </form>
    </div>
</div>

</div>

<div class="col-lg-4">
    <div class="card border-info">
        <div class="card-header bg-info text-white"><strong><i class="bi bi-info-circle"></i> Informacja</strong></div>
        <div class="card-body small">
            <p>Flagi funkcji pozwalają na selektywne włączanie i wyłączanie modułów systemu bez modyfikacji kodu.</p>
            <p>Wyłączenie modułu:</p>
            <ul>
                <li>Ukrywa go w interfejsie użytkownika</li>
                <li>Przekierowuje bezpośredni dostęp do dashboardu</li>
                <li>Nie usuwa danych z bazy</li>
            </ul>
            <p class="mb-0">Moduły oznaczone jako <span class="badge bg-warning text-dark">BETA</span> mogą działać niestabilnie.</p>
        </div>
    </div>
</div>

</div>
