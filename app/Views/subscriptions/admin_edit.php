<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('admin/subscriptions') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0">Subskrypcja: <?= e($sub['club_name'] ?? 'Klub #' . $clubId) ?></h2>
</div>

<div class="card" style="max-width:520px">
    <div class="card-body">
        <form method="post" action="<?= url('admin/subscriptions/' . $clubId) ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Plan</label>
                <select name="plan" class="form-select">
                    <?php foreach ($plans as $key => $plan): ?>
                        <option value="<?= $key ?>" <?= ($sub['plan'] ?? '') === $key ? 'selected' : '' ?>>
                            <?= e($plan['label']) ?>
                            <?php if ($plan['max_members']): ?> — max <?= $plan['max_members'] ?> zawodników<?php endif; ?>
                            (<?= $plan['price_pln'] > 0 ? $plan['price_pln'] . ' PLN/mies.' : 'bezpłatny' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= ($sub['status'] ?? '') === 'active' ? 'selected' : '' ?>>Aktywny</option>
                    <option value="expired" <?= ($sub['status'] ?? '') === 'expired' ? 'selected' : '' ?>>Wygasły</option>
                    <option value="cancelled" <?= ($sub['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Anulowany</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Ważny do (puste = bezterminowo)</label>
                <input type="date" name="valid_until" class="form-control"
                       value="<?= e($sub['valid_until'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Max zawodników (puste = nieograniczone)</label>
                <input type="number" name="max_members" class="form-control" min="1"
                       value="<?= e($sub['max_members'] ?? '') ?>" placeholder="Nieograniczone">
            </div>
            <div class="mb-3">
                <label class="form-label">Uwagi</label>
                <textarea name="notes" class="form-control" rows="2"><?= e($sub['notes'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2"></i> Zapisz
            </button>
            <a href="<?= url('admin/subscriptions') ?>" class="btn btn-outline-secondary ms-2">Anuluj</a>
        </form>
    </div>
</div>
