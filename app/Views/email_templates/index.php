<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('club/settings') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-envelope-gear"></i> Szablony e-mail</h2>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Typ powiadomienia</th>
                    <th>Temat</th>
                    <th class="text-center">Źródło</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($templates as $type => $tpl): ?>
                <tr>
                    <td><strong><?= e($tpl['label']) ?></strong></td>
                    <td class="text-muted small"><?= e($tpl['subject']) ?></td>
                    <td class="text-center">
                        <?php if ($tpl['is_custom']): ?>
                            <span class="badge bg-primary">Własny</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Domyślny</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="<?= url('club/email-templates/' . $type . '/edit') ?>"
                           class="btn btn-sm btn-outline-primary py-0">
                            <i class="bi bi-pencil"></i> Edytuj
                        </a>
                        <?php if ($tpl['is_custom']): ?>
                        <form method="post" action="<?= url('club/email-templates/' . $type . '/reset') ?>"
                              class="d-inline" onsubmit="return confirm('Przywrócić szablon domyślny?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-secondary py-0">
                                <i class="bi bi-arrow-counterclockwise"></i> Przywróć
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body small text-muted">
        <i class="bi bi-info-circle"></i>
        Dostępne zmienne: <code>{{member_name}}</code>, <code>{{competition_name}}</code>,
        <code>{{competition_date}}</code>, <code>{{competition_location}}</code>,
        <code>{{license_number}}</code>, <code>{{valid_until}}</code>,
        <code>{{exam_type}}</code>, <code>{{period}}</code>, <code>{{amount}}</code>,
        <code>{{club_name}}</code>, <code>{{portal_url}}</code>.
        Własne szablony nadpisują domyślne.
    </div>
</div>
