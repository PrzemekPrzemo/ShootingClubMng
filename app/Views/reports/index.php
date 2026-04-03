<h2 class="h4 mb-4"><i class="bi bi-file-earmark-bar-graph"></i> Raporty</h2>

<div class="row g-3">
    <div class="col-md-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-people text-danger" style="font-size:2rem"></i>
                <h5 class="mt-2">Zawodnicy</h5>
                <p class="text-muted small">Lista wszystkich zawodników z filtrami statusu i typu.</p>
                <a href="<?= url('reports/members') ?>" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-eye"></i> Podgląd
                </a>
                <a href="<?= url('reports/members?format=csv') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-file-earmark-arrow-down"></i> CSV
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-cash-stack text-success" style="font-size:2rem"></i>
                <h5 class="mt-2">Finanse</h5>
                <p class="text-muted small">Wpłaty i zaległości z podziałem na rok i typ.</p>
                <a href="<?= url('reports/finances') ?>" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-eye"></i> Podgląd
                </a>
                <a href="<?= url('reports/finances?format=csv') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-file-earmark-arrow-down"></i> CSV
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-card-checklist text-primary" style="font-size:2rem"></i>
                <h5 class="mt-2">Licencje</h5>
                <p class="text-muted small">Stan licencji PZSS i terminy ważności.</p>
                <a href="<?= url('reports/licenses') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-eye"></i> Podgląd
                </a>
                <a href="<?= url('reports/licenses?format=csv') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-file-earmark-arrow-down"></i> CSV
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-trophy text-warning" style="font-size:2rem"></i>
                <h5 class="mt-2">Zawody</h5>
                <p class="text-muted small">Historia zawodów z liczbą zgłoszeń.</p>
                <a href="<?= url('reports/competitions') ?>" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-eye"></i> Podgląd
                </a>
                <a href="<?= url('reports/competitions?format=csv') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-file-earmark-arrow-down"></i> CSV
                </a>
            </div>
        </div>
    </div>
</div>
