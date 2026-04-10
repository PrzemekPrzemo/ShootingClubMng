<?php
/**
 * @var array  $clubs
 */
?>
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="fw-bold">Wyniki zawodów strzeleckich</h1>
        <p class="text-muted">Wybierz klub, aby zobaczyć wyniki i protokoły zawodów.</p>
    </div>

    <?php if (empty($clubs)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle me-2"></i>Brak opublikowanych wyników zawodów.
        </div>
    <?php else: ?>
    <div class="row g-4 justify-content-center">
        <?php foreach ($clubs as $club): ?>
        <?php
        $slug = $club['subdomain'] ?: ($club['short_name'] ?: $club['id']);
        ?>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <a href="<?= url('pub/' . e($slug) . '/competitions') ?>"
               class="card h-100 text-decoration-none border-0 shadow-sm"
               style="transition:.15s">
                <div class="card-body text-center p-4">
                    <?php if (!empty($club['logo_path'])): ?>
                        <img src="<?= url('club-logo/' . (int)$club['id']) ?>"
                             alt="<?= e($club['name']) ?>"
                             style="height:48px;max-width:120px;object-fit:contain;margin-bottom:.75rem">
                    <?php else: ?>
                        <i class="bi bi-bullseye text-primary mb-2" style="font-size:2rem;display:block"></i>
                    <?php endif; ?>
                    <h6 class="fw-bold mb-1"><?= e($club['name']) ?></h6>
                    <?php if (!empty($club['short_name'])): ?>
                        <small class="text-muted"><?= e($club['short_name']) ?></small>
                    <?php endif; ?>
                    <?php if ($club['public_competitions'] > 0): ?>
                        <div class="mt-2">
                            <span class="badge bg-primary"><?= (int)$club['public_competitions'] ?> wynik<?= $club['public_competitions'] == 1 ? '' : 'ów' ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
