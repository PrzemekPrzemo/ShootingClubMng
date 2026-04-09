<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('admin/ads') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= $ad ? 'Edytuj reklamę' : 'Nowa reklama' ?></h2>
</div>

<div class="card" style="max-width:680px">
    <div class="card-body">
        <form method="post" action="<?= $ad ? url('admin/ads/' . $ad['id']) : url('admin/ads') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Tytuł (wewnętrzny) *</label>
                <input type="text" name="title" class="form-control" required
                       value="<?= e($ad['title'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Treść (HTML) *</label>
                <textarea name="content" class="form-control font-monospace" rows="4" required><?= e($ad['content'] ?? '') ?></textarea>
                <div class="form-text">Obsługuje HTML. Przykład: <code>&lt;strong&gt;Tekst&lt;/strong&gt;</code>, tagi &lt;a&gt;, &lt;img&gt;.</div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Link URL (opcjonalny)</label>
                    <input type="url" name="link_url" class="form-control"
                           value="<?= e($ad['link_url'] ?? '') ?>" placeholder="https://...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ścieżka obrazka (opcjonalny)</label>
                    <input type="text" name="image_path" class="form-control"
                           value="<?= e($ad['image_path'] ?? '') ?>" placeholder="/storage/ads/banner.jpg">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Wyświetlaj w</label>
                <?php
                $currentTargets = isset($ad['target']) ? explode(',', $ad['target']) : ['club_ui','member_portal'];
                ?>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="target[]" value="club_ui" id="t_club"
                               <?= in_array('club_ui', $currentTargets) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="t_club">Panel klubowy (club_ui)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="target[]" value="member_portal" id="t_portal"
                               <?= in_array('member_portal', $currentTargets) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="t_portal">Portal zawodnika (member_portal)</label>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tylko dla klubu (opcjonalnie)</label>
                    <select name="club_id" class="form-select">
                        <option value="">— Wszystkie kluby —</option>
                        <?php foreach ($clubs as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($ad['club_id'] ?? null) == $c['id'] ? 'selected' : '' ?>>
                                <?= e($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tylko dla planów (opcjonalnie)</label>
                    <?php
                    $allPlans     = ['trial','basic','standard','premium'];
                    $currentPlans = isset($ad['plan_keys']) ? explode(',', $ad['plan_keys']) : [];
                    ?>
                    <div class="d-flex flex-wrap gap-2 mt-1">
                    <?php foreach ($allPlans as $pk): ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="plan_keys[]" value="<?= $pk ?>"
                                   id="pk_<?= $pk ?>" <?= in_array($pk, $currentPlans) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="pk_<?= $pk ?>"><?= $pk ?></label>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Aktywna od</label>
                    <input type="date" name="starts_at" class="form-control"
                           value="<?= e($ad['starts_at'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Aktywna do</label>
                    <input type="date" name="ends_at" class="form-control"
                           value="<?= e($ad['ends_at'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kolejność</label>
                    <input type="number" name="sort_order" class="form-control" min="0"
                           value="<?= (int)($ad['sort_order'] ?? 0) ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                               <?= ($ad['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Aktywna</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
            <a href="<?= url('admin/ads') ?>" class="btn btn-outline-secondary ms-2">Anuluj</a>
        </form>
    </div>
</div>
