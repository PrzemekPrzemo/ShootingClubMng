<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('members') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0">Import zawodników z CSV</h2>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><strong>Wczytaj plik CSV</strong></div>
            <div class="card-body">
                <form method="post" action="<?= url('members/import') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Plik CSV</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
                        <div class="form-text">
                            Dozwolone formaty: .csv, .txt. Separator: przecinek lub średnik. Kodowanie: UTF-8 lub Windows-1250.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Separator kolumn</label>
                        <select name="delimiter" class="form-select form-select-sm" style="width:auto">
                            <option value="auto" selected>Automatycznie</option>
                            <option value=",">Przecinek (,)</option>
                            <option value=";">Średnik (;)</option>
                            <option value="\t">Tab</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Domyślny typ członkostwa (gdy brak w pliku)</label>
                        <select name="default_type" class="form-select form-select-sm" style="width:auto">
                            <option value="rekreacyjny">Rekreacyjny</option>
                            <option value="wyczynowy">Wyczynowy</option>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="has_header" value="1" id="hasHeader" checked>
                        <label class="form-check-label" for="hasHeader">Pierwszy wiersz to nagłówek</label>
                    </div>
                    <button type="submit" name="action" value="preview" class="btn btn-outline-primary">
                        <i class="bi bi-eye"></i> Podgląd (bez importu)
                    </button>
                    <button type="submit" name="action" value="import" class="btn btn-success ms-2">
                        <i class="bi bi-upload"></i> Importuj
                    </button>
                </form>
            </div>
        </div>

        <?php if (!empty($preview)): ?>
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Podgląd — <?= count($preview) ?> wierszy</strong>
                <?php if (!empty($importResult)): ?>
                    <span class="badge bg-success"><?= $importResult['imported'] ?> zaimportowano</span>
                    <?php if ($importResult['skipped'] > 0): ?>
                        <span class="badge bg-warning ms-1"><?= $importResult['skipped'] ?> pominięto</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div style="max-height:400px;overflow-y:auto">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-dark sticky-top">
                        <tr>
                            <th>#</th>
                            <th>Nazwisko</th>
                            <th>Imię</th>
                            <th>PESEL</th>
                            <th>Data ur.</th>
                            <th>Typ</th>
                            <th>E-mail</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($preview as $i => $row): ?>
                        <tr class="<?= $row['_error'] ? 'table-danger' : '' ?>">
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td><?= e($row['last_name'] ?? '') ?></td>
                            <td><?= e($row['first_name'] ?? '') ?></td>
                            <td class="text-muted small"><?= e($row['pesel'] ?? '—') ?></td>
                            <td class="small"><?= e($row['birth_date'] ?? '—') ?></td>
                            <td><?= e($row['member_type'] ?? '') ?></td>
                            <td class="small"><?= e($row['email'] ?? '—') ?></td>
                            <td>
                                <?php if ($row['_error']): ?>
                                    <span class="text-danger small"><i class="bi bi-x-circle"></i> <?= e($row['_error']) ?></span>
                                <?php elseif (isset($row['_imported'])): ?>
                                    <span class="text-success small"><i class="bi bi-check-circle"></i> Zaimportowano (<?= e($row['member_number'] ?? '') ?>)</span>
                                <?php else: ?>
                                    <span class="text-success small"><i class="bi bi-check2"></i> OK</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><strong><i class="bi bi-download"></i> Szablon CSV</strong></div>
            <div class="card-body">
                <p class="small text-muted">Pobierz szablon i uzupełnij danymi. Nagłówki kolumn muszą pasować do poniższych (wielkość liter nie ma znaczenia).</p>
                <a href="<?= url('members/import/template') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Pobierz szablon CSV
                </a>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header"><strong>Obsługiwane kolumny</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Kolumna</th><th>Wymagana</th><th>Opis</th></tr></thead>
                    <tbody>
                        <tr><td><code>last_name</code></td><td><i class="bi bi-check text-success"></i></td><td>Nazwisko</td></tr>
                        <tr><td><code>first_name</code></td><td><i class="bi bi-check text-success"></i></td><td>Imię</td></tr>
                        <tr><td><code>pesel</code></td><td></td><td>PESEL (11 cyfr)</td></tr>
                        <tr><td><code>birth_date</code></td><td></td><td>Data urodzenia (YYYY-MM-DD)</td></tr>
                        <tr><td><code>gender</code></td><td></td><td>Płeć: M lub K</td></tr>
                        <tr><td><code>email</code></td><td></td><td>Adres e-mail</td></tr>
                        <tr><td><code>phone</code></td><td></td><td>Telefon</td></tr>
                        <tr><td><code>member_type</code></td><td></td><td>rekreacyjny / wyczynowy</td></tr>
                        <tr><td><code>join_date</code></td><td></td><td>Data wstąpienia (YYYY-MM-DD)</td></tr>
                        <tr><td><code>status</code></td><td></td><td>aktywny / zawieszony</td></tr>
                        <tr><td><code>address_street</code></td><td></td><td>Ulica i nr</td></tr>
                        <tr><td><code>address_city</code></td><td></td><td>Miasto</td></tr>
                        <tr><td><code>address_postal</code></td><td></td><td>Kod pocztowy</td></tr>
                        <tr><td><code>notes</code></td><td></td><td>Uwagi</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
