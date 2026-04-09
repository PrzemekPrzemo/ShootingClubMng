<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('members/' . $member['id']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0">Zgody RODO — <?= e($member['last_name']) ?> <?= e($member['first_name']) ?></h2>
</div>

<?php
$consentLabels = [
    'data_processing' => 'Przetwarzanie danych osobowych',
    'marketing'       => 'Komunikacja marketingowa',
    'photo'           => 'Publikacja wizerunku (zdjęcia)',
    'medical_data'    => 'Przetwarzanie danych medycznych (badania)',
];
?>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><strong>Zarządzanie zgodami</strong></div>
            <div class="card-body">
                <form method="post" action="<?= url('members/' . $member['id'] . '/gdpr/consents') ?>">
                    <?= csrf_field() ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Typ zgody</th>
                                <th style="width:80px" class="text-center">Udzielona</th>
                                <th>Data udzielenia</th>
                                <th>Data cofnięcia</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($consentLabels as $type => $label): ?>
                            <?php $c = $consents[$type] ?? null; $isGranted = $c && $c['granted_at'] && !$c['revoked_at']; ?>
                            <tr>
                                <td><?= e($label) ?></td>
                                <td class="text-center">
                                    <div class="form-check d-flex justify-content-center">
                                        <input class="form-check-input" type="checkbox"
                                               name="consent[]" value="<?= $type ?>"
                                               <?= $isGranted ? 'checked' : '' ?>>
                                    </div>
                                </td>
                                <td class="text-muted small">
                                    <?= $c && $c['granted_at'] ? date('d.m.Y H:i', strtotime($c['granted_at'])) : '—' ?>
                                </td>
                                <td class="text-muted small">
                                    <?php if ($c && $c['revoked_at']): ?>
                                        <span class="text-warning"><?= date('d.m.Y H:i', strtotime($c['revoked_at'])) ?></span>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check2"></i> Zapisz zgody
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header text-danger"><strong><i class="bi bi-shield-x"></i> Prawo do bycia zapomnianym</strong></div>
            <div class="card-body">
                <p class="small text-muted">
                    Anonimizacja usuwa wszystkie dane osobowe zawodnika (imię, PESEL, adres, zdjęcie, dokumenty medyczne)
                    zgodnie z art. 17 RODO. <strong>Operacja jest nieodwracalna.</strong>
                    Rekordy statystyczne (zawody, składki) pozostają z numerem członkowskim.
                </p>
                <?php if (empty($member['anonymized_at'])): ?>
                <form method="post" action="<?= url('members/' . $member['id'] . '/gdpr/anonymize') ?>"
                      onsubmit="return confirm('UWAGA: Anonimizacja jest nieodwracalna. Czy na pewno chcesz usunąć wszystkie dane osobowe tego zawodnika?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-eraser"></i> Anonimizuj dane (art. 17 RODO)
                    </button>
                </form>
                <?php else: ?>
                <div class="alert alert-secondary py-2 small">
                    <i class="bi bi-check-circle"></i>
                    Dane zanonimizowano: <?= date('d.m.Y H:i', strtotime($member['anonymized_at'])) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header"><strong><i class="bi bi-download"></i> Eksport danych (art. 20 RODO)</strong></div>
            <div class="card-body">
                <p class="small text-muted">
                    Pobierz plik JSON ze wszystkimi danymi zawodnika (prawo do przenoszenia danych).
                </p>
                <?php if (!empty($member['gdpr_export_at'])): ?>
                <p class="small text-muted">Ostatni eksport: <?= date('d.m.Y H:i', strtotime($member['gdpr_export_at'])) ?></p>
                <?php endif; ?>
                <form method="post" action="<?= url('members/' . $member['id'] . '/gdpr/export') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-file-earmark-code"></i> Pobierz JSON
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
