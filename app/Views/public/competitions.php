<div class="d-flex align-items-center mb-3 gap-2">
    <h2 class="h4 mb-0"><i class="bi bi-trophy"></i> <?= e($club['name']) ?> — Zawody</h2>
</div>

<?php if (empty($competitions)): ?>
    <div class="alert alert-info">Brak opublikowanych wyników zawodów.</div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($competitions as $c): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-1"><?= e($c['name']) ?></h5>
                <p class="text-muted small mb-2">
                    <i class="bi bi-calendar3"></i>
                    <?= date('d.m.Y', strtotime($c['competition_date'])) ?>
                    <?php if ($c['location']): ?>
                        &nbsp;|&nbsp; <i class="bi bi-geo-alt"></i> <?= e($c['location']) ?>
                    <?php endif; ?>
                </p>
                <span class="badge bg-<?= $c['status'] === 'zakonczone' ? 'success' : 'secondary' ?>">
                    <?= e($c['status']) ?>
                </span>
            </div>
            <div class="card-footer bg-transparent">
                <a href="<?= url('pub/' . $slug . '/competitions/' . $c['id']) ?>"
                   class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-list-ol"></i> Zobacz wyniki
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
