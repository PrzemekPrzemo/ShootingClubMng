<?php $isEdit = !empty($club); ?>
<h2 class="mb-4">
    <i class="bi bi-building"></i>
    <?= $isEdit ? 'Edycja klubu' : 'Nowy klub' ?>
</h2>

<div class="card" style="max-width:700px">
    <div class="card-body">
        <form method="post" action="<?= $isEdit ? url("admin/clubs/{$club['id']}/edit") : url('admin/clubs/create') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="name" class="form-label">Nazwa klubu *</label>
                <input type="text" class="form-control" id="name" name="name"
                       value="<?= e($club['name'] ?? '') ?>" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="short_name" class="form-label">Skrót</label>
                    <input type="text" class="form-control" id="short_name" name="short_name"
                           value="<?= e($club['short_name'] ?? '') ?>" maxlength="50">
                </div>
                <div class="col-md-8">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= e($club['email'] ?? '') ?>">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="phone" class="form-label">Telefon</label>
                    <input type="text" class="form-control" id="phone" name="phone"
                           value="<?= e($club['phone'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label for="nip" class="form-label">NIP</label>
                    <input type="text" class="form-control" id="nip" name="nip"
                           value="<?= e($club['nip'] ?? '') ?>" maxlength="15">
                </div>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Adres</label>
                <textarea class="form-control" id="address" name="address" rows="2"><?= e($club['address'] ?? '') ?></textarea>
            </div>

            <?php if ($isEdit): ?>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                       <?= $club['is_active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Aktywny</label>
            </div>
            <?php endif; ?>

            <hr class="my-4">
            <h6 class="text-uppercase text-muted small mb-3"><i class="bi bi-credit-card-2-front"></i> Subskrypcja / Limity</h6>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Plan</label>
                    <select class="form-select" name="plan">
                        <option value="">— Brak —</option>
                        <?php foreach (['trial' => 'Trial (próbny)', 'basic' => 'Basic', 'standard' => 'Standard', 'premium' => 'Premium'] as $planKey => $planLabel): ?>
                        <option value="<?= $planKey ?>" <?= ($subscription['plan'] ?? '') === $planKey ? 'selected' : '' ?>>
                            <?= $planLabel ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ważny do</label>
                    <input type="date" class="form-control" name="valid_until"
                           value="<?= e($subscription['valid_until'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="sub_status">
                        <option value="active"    <?= ($subscription['status'] ?? 'active') === 'active'    ? 'selected' : '' ?>>Aktywny</option>
                        <option value="expired"   <?= ($subscription['status'] ?? '')        === 'expired'   ? 'selected' : '' ?>>Wygasły</option>
                        <option value="cancelled" <?= ($subscription['status'] ?? '')        === 'cancelled' ? 'selected' : '' ?>>Anulowany</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Maksymalna liczba zawodników</label>
                <input type="number" class="form-control" name="max_members" min="1" style="max-width:200px"
                       value="<?= e($subscription['max_members'] ?? '') ?>"
                       placeholder="bez limitu">
                <div class="form-text">Pozostaw puste = bez limitu.</div>
            </div>

            <hr class="my-4">
            <h6 class="text-uppercase text-muted small mb-1"><i class="bi bi-toggles"></i> Aktywne moduły</h6>
            <p class="text-muted small mb-3">Odznaczone moduły nie będą widoczne w menu bocznym użytkowników tego klubu.</p>

            <div class="row g-2 mb-4">
                <?php foreach (\App\Models\RolePermissionModel::MODULES as $mod => $cfg):
                    if ($mod === 'dashboard') continue; ?>
                <div class="col-md-4 col-lg-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="modules[]"
                               value="<?= $mod ?>" id="mod_<?= $mod ?>"
                               <?= ($clubModules[$mod] ?? true) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="mod_<?= $mod ?>">
                            <i class="bi bi-<?= $cfg['icon'] ?>"></i> <?= e($cfg['label']) ?>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Zapisz' : 'Utwórz' ?>
                </button>
                <a href="<?= url('admin/clubs') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>
