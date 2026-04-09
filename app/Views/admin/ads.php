<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('admin/dashboard') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-megaphone"></i> Reklamy w systemie</h2>
    <a href="<?= url('admin/ads/create') ?>" class="btn btn-sm btn-primary ms-auto">
        <i class="bi bi-plus-lg"></i> Nowa reklama
    </a>
</div>

<div class="alert alert-info small">
    <i class="bi bi-info-circle"></i>
    Reklamy są wyświetlane w widoku klubowym (<strong>club_ui</strong>) i/lub portalu zawodnika (<strong>member_portal</strong>).
    Możesz targetować konkretny klub lub plan subskrypcji.
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th style="width:40px">#</th>
                    <th>Tytuł</th>
                    <th>Cel</th>
                    <th>Klub</th>
                    <th>Plany</th>
                    <th>Termin</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ads as $ad): ?>
                <tr class="<?= !$ad['is_active'] ? 'text-muted' : '' ?>">
                    <td><?= $ad['sort_order'] ?></td>
                    <td>
                        <strong><?= e($ad['title']) ?></strong>
                        <?php if ($ad['link_url']): ?>
                            <br><small class="text-muted"><?= e(parse_url($ad['link_url'], PHP_URL_HOST)) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php foreach (explode(',', $ad['target']) as $t): ?>
                            <span class="badge bg-<?= $t === 'club_ui' ? 'primary' : 'info' ?>"><?= e($t) ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td class="small"><?= $ad['club_name'] ? e($ad['club_name']) : '<span class="text-muted">Wszystkie</span>' ?></td>
                    <td class="small"><?= $ad['plan_keys'] ? e($ad['plan_keys']) : '<span class="text-muted">Wszystkie</span>' ?></td>
                    <td class="small text-muted">
                        <?= $ad['starts_at'] ? date('d.m.Y', strtotime($ad['starts_at'])) : '∞' ?>
                        –
                        <?= $ad['ends_at']   ? date('d.m.Y', strtotime($ad['ends_at']))   : '∞' ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $ad['is_active'] ? 'success' : 'secondary' ?>">
                            <?= $ad['is_active'] ? 'Aktywna' : 'Wyłączona' ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="<?= url('admin/ads/' . $ad['id'] . '/edit') ?>" class="btn btn-xs btn-outline-secondary py-0 px-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="post" action="<?= url('admin/ads/' . $ad['id'] . '/toggle') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-xs btn-outline-<?= $ad['is_active'] ? 'warning' : 'success' ?> py-0 px-1" title="<?= $ad['is_active'] ? 'Wyłącz' : 'Włącz' ?>">
                                <i class="bi bi-<?= $ad['is_active'] ? 'pause' : 'play' ?>-fill"></i>
                            </button>
                        </form>
                        <form method="post" action="<?= url('admin/ads/' . $ad['id'] . '/delete') ?>" class="d-inline"
                              onsubmit="return confirm('Usunąć reklamę?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-xs btn-outline-danger py-0 px-1">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($ads)): ?>
                <tr><td colspan="8" class="text-muted text-center py-3">Brak reklam.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
