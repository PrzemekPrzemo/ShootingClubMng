<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('startlist/' . $generator['id']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>

<div class="row g-4">
    <!-- Upload form -->
    <div class="col-lg-5">

        <!-- Current state -->
        <?php if (!empty($competitors)): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 py-2">
            <i class="bi bi-people-fill"></i>
            <div>Zaimportowano <strong><?= count($competitors) ?></strong> zawodników.
                Wygeneruj harmonogram lub wgraj nowy plik, aby zastąpić dane.</div>
        </div>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-upload me-1"></i> Wgraj plik CSV</h6></div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data"
                      action="<?= url('startlist/' . $generator['id'] . '/import') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="preview">

                    <div class="mb-3">
                        <label class="form-label form-label-sm">Plik CSV <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control form-control-sm"
                               accept=".csv,.txt" required>
                        <div class="form-text">UTF-8 lub Windows-1250, separator <code>;</code> lub <code>,</code>.</div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="has_header" id="hasHeader" value="1" checked>
                        <label class="form-check-label form-label-sm" for="hasHeader">Pierwszy wiersz to nagłówek</label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-eye"></i> Podgląd danych
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Template download -->
        <div class="card border-secondary">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-spreadsheet text-success fs-5"></i>
                    <div>
                        <div class="small fw-semibold">Pobierz szablon CSV</div>
                        <div class="text-muted" style="font-size:.8rem">Kolumny: first_name; last_name; birth_date; gender; disciplines</div>
                    </div>
                    <a href="<?= url('startlist/' . $generator['id'] . '/import/template') ?>"
                       class="btn btn-sm btn-outline-success ms-auto">
                        <i class="bi bi-download"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Format instructions -->
        <div class="card mt-3 bg-light border-0">
            <div class="card-body py-2 small">
                <strong>Format pliku:</strong>
                <ul class="mb-0 ps-3 mt-1">
                    <li><code>birth_date</code> — YYYY-MM-DD</li>
                    <li><code>gender</code> — <code>M</code> lub <code>K</code></li>
                    <li><code>disciplines</code> — kody rozdzielone <code>;</code> lub <code>,</code><br>
                        np. <code>ppn;pst</code> lub <code>ppn,pst</code></li>
                </ul>
                <?php if (!empty($disciplines)): ?>
                <div class="mt-2"><strong>Kody dyscyplin w tym generatorze:</strong><br>
                    <?php foreach ($disciplines as $d): ?>
                        <span class="badge bg-secondary me-1"><?= e($d['code']) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Navigation -->
        <div class="d-flex gap-2 mt-3">
            <a href="<?= url('startlist/' . $generator['id'] . '/age-categories') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Krok 4: Kategorie
            </a>
            <a href="<?= url('startlist/' . $generator['id']) ?>" class="btn btn-outline-primary btn-sm ms-auto">
                Krok 6: Generuj <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Preview table -->
    <?php if ($preview !== null): ?>
    <div class="col-lg-7">
        <?php
        $errorCount = count(array_filter($preview, fn($r) => !empty($r['_error'])));
        $validCount = count($preview) - $errorCount;
        ?>
        <div class="alert alert-<?= $errorCount > 0 ? 'warning' : 'success' ?> py-2">
            <i class="bi bi-<?= $errorCount > 0 ? 'exclamation-triangle' : 'check-circle' ?>"></i>
            Wiersze: <strong><?= count($preview) ?></strong> łącznie,
            <strong class="text-success"><?= $validCount ?></strong> poprawnych,
            <?php if ($errorCount > 0): ?>
            <strong class="text-danger"><?= $errorCount ?></strong> z błędami (zostaną pominięte).
            <?php else: ?>
            brak błędów.
            <?php endif; ?>
        </div>

        <?php if ($validCount > 0): ?>
        <form method="post" enctype="multipart/form-data"
              action="<?= url('startlist/' . $generator['id'] . '/import') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="import">
            <!-- Re-upload the same file when confirming import -->
            <div class="mb-2 d-flex justify-content-end">
                <label class="form-label form-label-sm me-2 mb-0 align-self-center text-muted small">
                    Prześlij ponownie plik aby zatwierdzić import:
                </label>
                <input type="file" name="csv_file" class="form-control form-control-sm w-auto" accept=".csv,.txt" required>
                <input type="hidden" name="has_header" value="1">
                <button type="submit" class="btn btn-danger btn-sm ms-2"
                        onclick="return confirm('Zaimportować <?= $validCount ?> zawodników? Poprzedni import zostanie zastąpiony.')">
                    <i class="bi bi-cloud-upload"></i> Importuj <?= $validCount ?> zawodników
                </button>
            </div>
        </form>
        <?php endif; ?>

        <div class="table-responsive" style="max-height:500px; overflow-y:auto">
            <table class="table table-sm table-hover table-bordered mb-0" style="font-size:.82rem">
                <thead class="table-dark sticky-top">
                    <tr>
                        <th>#</th>
                        <th>Imię</th>
                        <th>Nazwisko</th>
                        <th>Data ur.</th>
                        <th>Płeć</th>
                        <th>Kategoria</th>
                        <th>Dyscypliny</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($preview as $i => $row): ?>
                    <tr class="<?= !empty($row['_error']) ? 'table-danger' : 'table-success' ?>">
                        <td class="text-muted"><?= $i + 1 ?></td>
                        <td><?= e($row['first_name']) ?></td>
                        <td><?= e($row['last_name']) ?></td>
                        <td><?= e($row['birth_date']) ?></td>
                        <td><?= e($row['gender']) ?></td>
                        <td><?= e($row['age_category_name'] ?? '') ?: '<span class="text-muted">—</span>' ?></td>
                        <td>
                            <?php if (!empty($row['discipline_ids'])): ?>
                                <code><?= e($row['discipline_codes']) ?></code>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($row['_error'])): ?>
                                <span class="text-danger"><i class="bi bi-x-circle"></i> <?= e($row['_error']) ?></span>
                            <?php else: ?>
                                <span class="text-success"><i class="bi bi-check-circle"></i> OK</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php elseif (!empty($competitors)): ?>
    <!-- Current competitors list (read-only) -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Aktualni zawodnicy (<?= count($competitors) ?>)</h6>
            </div>
            <div class="table-responsive" style="max-height:500px; overflow-y:auto">
                <table class="table table-sm table-hover mb-0" style="font-size:.82rem">
                    <thead class="table-dark sticky-top">
                        <tr>
                            <th>#</th>
                            <th>Imię i nazwisko</th>
                            <th>Data ur.</th>
                            <th>Płeć</th>
                            <th>Dyscypliny</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($competitors as $i => $comp): ?>
                        <tr>
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td><?= e($comp['first_name'] . ' ' . $comp['last_name']) ?></td>
                            <td><?= e($comp['birth_date'] ?? '') ?></td>
                            <td><?= e($comp['gender'] ?? '') ?></td>
                            <td><code><?= e($comp['discipline_codes'] ?? '') ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
