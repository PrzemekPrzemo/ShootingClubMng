<div class="card shadow-sm" style="max-width:460px;margin:0 auto">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <i class="bi bi-building text-primary" style="font-size:2.5rem"></i>
            <h4 class="mt-2 mb-0 fw-bold">Wybierz klub</h4>
            <p class="text-muted small">Twoje konto jest przypisane do kilku klubów</p>
        </div>

        <div class="list-group">
            <?php foreach ($clubs as $club): ?>
                <a href="<?= url('club-select/' . (int)$club['club_id']) ?>"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                   onclick="event.preventDefault(); document.getElementById('form-<?= (int)$club['club_id'] ?>').submit();">
                    <div>
                        <strong><?= e($club['club_name']) ?></strong>
                        <?php if (!empty($club['short_name'])): ?>
                            <span class="text-muted">(<?= e($club['short_name']) ?>)</span>
                        <?php endif; ?>
                        <br>
                        <small class="text-muted">Rola: <?= e($club['role']) ?></small>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <form id="form-<?= (int)$club['club_id'] ?>"
                      method="post"
                      action="<?= url('club-select/' . (int)$club['club_id']) ?>"
                      style="display:none">
                    <?= csrf_field() ?>
                </form>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-3">
            <a href="<?= url('auth/logout') ?>" class="text-muted small">
                <i class="bi bi-box-arrow-left"></i> Wyloguj się
            </a>
        </div>
    </div>
</div>
