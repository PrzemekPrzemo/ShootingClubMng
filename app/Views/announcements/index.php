<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><i class="bi bi-megaphone"></i> Ogłoszenia</h2>
    <a href="<?= url('announcements/create') ?>" class="btn btn-danger btn-sm">
        <i class="bi bi-plus-lg"></i> Nowe ogłoszenie
    </a>
</div>

<?php if (empty($announcements)): ?>
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-megaphone display-4 text-muted"></i>
        <p class="mt-3">Brak ogłoszeń. <a href="<?= url('announcements/create') ?>">Utwórz pierwsze ogłoszenie.</a></p>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Priorytet</th>
                        <th>Tytuł</th>
                        <th>Status</th>
                        <th>Opublikowano</th>
                        <th>Wygasa</th>
                        <th>Autor</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($announcements as $a):
                    $priorityBadge = match($a['priority']) {
                        'pilne' => ['danger',  'Pilne'],
                        'wazne' => ['warning text-dark', 'Ważne'],
                        default => ['secondary', 'Normalne'],
                    };
                    $isExpired = $a['expires_at'] && $a['expires_at'] < date('Y-m-d');
                ?>
                    <tr class="<?= $isExpired ? 'text-muted' : '' ?>">
                        <td>
                            <span class="badge bg-<?= $priorityBadge[0] ?>"><?= $priorityBadge[1] ?></span>
                        </td>
                        <td>
                            <span class="fw-semibold"><?= e($a['title']) ?></span>
                            <?php if ($a['body']): ?>
                                <div class="text-muted small text-truncate" style="max-width:300px">
                                    <?= e(strip_tags(mb_strimwidth($a['body'], 0, 80, '…'))) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isExpired): ?>
                                <span class="badge bg-secondary">Wygasłe</span>
                            <?php elseif ($a['is_published']): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Opublikowane</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark border">Ukryte</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <?= $a['published_at'] ? format_date(substr($a['published_at'], 0, 10)) : '—' ?>
                        </td>
                        <td class="small text-muted">
                            <?= $a['expires_at'] ? format_date($a['expires_at']) : '—' ?>
                        </td>
                        <td class="small text-muted"><?= e($a['created_by_name'] ?? '—') ?></td>
                        <td class="text-end" style="white-space:nowrap">
                            <!-- Toggle publish -->
                            <form method="post"
                                  action="<?= url('announcements/' . $a['id'] . '/toggle-publish') ?>"
                                  style="display:inline">
                                <?= csrf_field() ?>
                                <button type="submit"
                                        class="btn btn-sm py-0 <?= $a['is_published'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                        title="<?= $a['is_published'] ? 'Ukryj' : 'Opublikuj' ?>">
                                    <i class="bi bi-<?= $a['is_published'] ? 'eye-slash' : 'eye' ?>"></i>
                                </button>
                            </form>
                            <a href="<?= url('announcements/' . $a['id'] . '/edit') ?>"
                               class="btn btn-outline-secondary btn-sm py-0" title="Edytuj">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="post"
                                  action="<?= url('announcements/' . $a['id'] . '/delete') ?>"
                                  style="display:inline"
                                  onsubmit="return confirm('Czy na pewno usunąć to ogłoszenie?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline-danger btn-sm py-0" title="Usuń">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<p class="text-muted small mt-2">Łącznie: <?= count($announcements) ?> ogłoszeń</p>
<?php endif; ?>
