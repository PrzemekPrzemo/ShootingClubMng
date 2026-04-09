<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('club/email-templates') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0">Edytuj szablon: <?= e($label) ?></h2>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="post" action="<?= url('club/email-templates/' . $type) ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Temat wiadomości</label>
                        <input type="text" name="subject" class="form-control" required
                               value="<?= e($template['subject'] ?? '') ?>">
                        <div class="form-text">Możesz używać zmiennych np. <code>{{competition_name}}</code>, <code>{{member_name}}</code></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Treść (HTML)</label>
                        <textarea name="body_html" class="form-control font-monospace"
                                  rows="16" style="font-size:.85rem"><?= e($template['body_html'] ?? '') ?></textarea>
                        <div class="form-text">
                            Możesz używać tagów HTML: <code>&lt;p&gt;</code>, <code>&lt;strong&gt;</code>, <code>&lt;a href="..."&gt;</code> itp.
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2"></i> Zapisz szablon
                        </button>
                        <a href="<?= url('club/email-templates') ?>" class="btn btn-outline-secondary">Anuluj</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><strong>Dostępne zmienne</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                    <?php
                    $vars = [
                        '{{member_name}}'         => 'Imię i nazwisko zawodnika',
                        '{{club_name}}'            => 'Nazwa klubu',
                        '{{competition_name}}'     => 'Nazwa zawodów',
                        '{{competition_date}}'     => 'Data zawodów',
                        '{{competition_location}}' => 'Miejsce zawodów',
                        '{{license_number}}'       => 'Numer licencji PZSS',
                        '{{valid_until}}'          => 'Data ważności',
                        '{{exam_type}}'            => 'Typ badania',
                        '{{period}}'               => 'Okres rozliczeniowy',
                        '{{amount}}'               => 'Kwota zaległości',
                        '{{portal_url}}'           => 'Link do portalu',
                    ];
                    foreach ($vars as $var => $desc): ?>
                    <tr>
                        <td><code class="small"><?= e($var) ?></code></td>
                        <td class="text-muted small"><?= e($desc) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (!empty($template['variables_hint'])): ?>
        <div class="card mt-3">
            <div class="card-header"><strong>Wskazówka</strong></div>
            <div class="card-body small text-muted"><?= e($template['variables_hint']) ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>
