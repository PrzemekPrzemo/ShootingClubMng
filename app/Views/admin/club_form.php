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

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Zapisz' : 'Utwórz' ?>
                </button>
                <a href="<?= url('admin/clubs') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>
