<h2 class="mb-4"><i class="bi bi-calculator"></i> Kalkulator cen</h2>
<p class="text-muted mb-4">Oszacuj roczny koszt subskrypcji dla nowego klubu. Ustaw liczbę członków, wybierz moduły i sprawdź cenę.</p>

<div class="row g-4">
    <!-- Left: Controls -->
    <div class="col-lg-7">
        <!-- Member slider -->
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-people me-1"></i> Liczba członków</h6></div>
            <div class="card-body">
                <input type="range" class="form-range" id="calc-members" min="10" max="510" value="100" step="10">
                <div class="d-flex justify-content-between">
                    <small class="text-muted">10</small>
                    <strong class="fs-5" id="calc-members-value">100</strong>
                    <small class="text-muted">500+</small>
                </div>
            </div>
        </div>

        <!-- Modules -->
        <div class="card" id="calc-modules-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-puzzle me-1"></i> Moduły dodatkowe</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="calc-select-all">Zaznacz wszystkie</button>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Cena modułu naliczana za każde rozpoczęte 100 członków / rok</p>
                <div class="row g-2" id="calc-modules">
                    <!-- Core (included) -->
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2 p-2 rounded border bg-body-tertiary opacity-75">
                            <input type="checkbox" class="form-check-input" checked disabled>
                            <i class="bi bi-people text-primary"></i>
                            <span class="flex-grow-1">Zarządzanie członkami</span>
                            <span class="badge bg-secondary">W cenie</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2 p-2 rounded border bg-body-tertiary opacity-75">
                            <input type="checkbox" class="form-check-input" checked disabled>
                            <i class="bi bi-card-checklist text-primary"></i>
                            <span class="flex-grow-1">Licencje PZSS</span>
                            <span class="badge bg-secondary">W cenie</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2 p-2 rounded border bg-body-tertiary opacity-75">
                            <input type="checkbox" class="form-check-input" checked disabled>
                            <i class="bi bi-heart-pulse text-primary"></i>
                            <span class="flex-grow-1">Badania lekarskie</span>
                            <span class="badge bg-secondary">W cenie</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2 p-2 rounded border bg-body-tertiary opacity-75">
                            <input type="checkbox" class="form-check-input" checked disabled>
                            <i class="bi bi-gear text-primary"></i>
                            <span class="flex-grow-1">Konfiguracja</span>
                            <span class="badge bg-secondary">W cenie</span>
                        </label>
                    </div>
                    <!-- Paid modules -->
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-price="290">
                            <input type="checkbox" class="form-check-input calc-module-cb">
                            <i class="bi bi-cash-stack text-warning"></i>
                            <span class="flex-grow-1">Finanse i składki</span>
                            <span class="text-muted small">290 PLN</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-price="390">
                            <input type="checkbox" class="form-check-input calc-module-cb">
                            <i class="bi bi-trophy text-warning"></i>
                            <span class="flex-grow-1">Zawody i wyniki</span>
                            <span class="text-muted small">390 PLN</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-price="190">
                            <input type="checkbox" class="form-check-input calc-module-cb">
                            <i class="bi bi-tools text-warning"></i>
                            <span class="flex-grow-1">Sprzęt i broń</span>
                            <span class="text-muted small">190 PLN</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-price="190">
                            <input type="checkbox" class="form-check-input calc-module-cb">
                            <i class="bi bi-calendar-event text-warning"></i>
                            <span class="flex-grow-1">Treningi i obecność</span>
                            <span class="text-muted small">190 PLN</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-price="290">
                            <input type="checkbox" class="form-check-input calc-module-cb">
                            <i class="bi bi-person-workspace text-warning"></i>
                            <span class="flex-grow-1">Portal członka</span>
                            <span class="text-muted small">290 PLN</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-price="190">
                            <input type="checkbox" class="form-check-input calc-module-cb">
                            <i class="bi bi-file-earmark-bar-graph text-warning"></i>
                            <span class="flex-grow-1">Raporty i analizy</span>
                            <span class="text-muted small">190 PLN</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-price="190">
                            <input type="checkbox" class="form-check-input calc-module-cb">
                            <i class="bi bi-envelope text-warning"></i>
                            <span class="flex-grow-1">Powiadomienia email</span>
                            <span class="text-muted small">190 PLN</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-price="90">
                            <input type="checkbox" class="form-check-input calc-module-cb">
                            <i class="bi bi-calendar3 text-warning"></i>
                            <span class="flex-grow-1">Kalendarz i wydarzenia</span>
                            <span class="text-muted small">90 PLN</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-price="90">
                            <input type="checkbox" class="form-check-input calc-module-cb">
                            <i class="bi bi-megaphone text-warning"></i>
                            <span class="flex-grow-1">Ogłoszenia</span>
                            <span class="text-muted small">90 PLN</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-price="90">
                            <input type="checkbox" class="form-check-input calc-module-cb">
                            <i class="bi bi-shield-check text-warning"></i>
                            <span class="flex-grow-1">Dashboard bezpieczeństwa</span>
                            <span class="text-muted small">90 PLN</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-price="90">
                            <input type="checkbox" class="form-check-input calc-module-cb">
                            <i class="bi bi-bank text-warning"></i>
                            <span class="flex-grow-1">Opłaty federacyjne</span>
                            <span class="text-muted small">90 PLN</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Summary -->
    <div class="col-lg-5">
        <div class="card border-primary sticky-top" style="top: 80px;" id="calc-summary">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-receipt me-1"></i> Szacowany koszt roczny</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-3">
                    <tbody>
                        <tr>
                            <td>Subskrypcja bazowa <small class="text-muted">(do 100 czł.)</small></td>
                            <td class="text-end fw-bold" id="calc-base-cost">990 PLN</td>
                        </tr>
                        <tr id="calc-members-row" style="display: none;">
                            <td>Dodatkowi członkowie <small class="text-muted">(<span id="calc-extra-count">0</span>)</small></td>
                            <td class="text-end" id="calc-members-cost">0 PLN</td>
                        </tr>
                        <tr>
                            <td>Wybrane moduły <small class="text-muted">(<span id="calc-modules-count">0</span>) × <span id="calc-blocks-count">1</span> blok(ów)</small></td>
                            <td class="text-end" id="calc-modules-cost">0 PLN</td>
                        </tr>
                        <tr id="calc-discount-row" style="display: none;">
                            <td class="text-success"><i class="bi bi-tag me-1"></i>Rabat za wszystkie moduły (-20%)</td>
                            <td class="text-end text-success fw-bold" id="calc-discount-amount">-0 PLN</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <td class="fw-bold fs-6">RAZEM</td>
                            <td class="text-end fw-bold fs-5"><span id="calc-total">990</span> PLN / rok</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Miesięcznie</td>
                            <td class="text-end text-muted small"><span id="calc-monthly">83</span> PLN / mies.</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Enterprise panel (500+) -->
        <div class="card border-warning mt-3" id="calc-enterprise" style="display: none;">
            <div class="card-body text-center py-4">
                <i class="bi bi-building fs-1 text-warning mb-2 d-block"></i>
                <h5>Powyżej 500 członków</h5>
                <p class="text-muted">Zapraszamy do indywidualnych negocjacji — dopasujemy ofertę do skali i potrzeb klubu.</p>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var slider = document.getElementById('calc-members');
    var sliderValue = document.getElementById('calc-members-value');
    var modulesCard = document.getElementById('calc-modules-card');
    var summaryCard = document.getElementById('calc-summary');
    var enterpriseCard = document.getElementById('calc-enterprise');
    var selectAllBtn = document.getElementById('calc-select-all');

    var baseCostEl = document.getElementById('calc-base-cost');
    var membersRowEl = document.getElementById('calc-members-row');
    var extraCountEl = document.getElementById('calc-extra-count');
    var membersCostEl = document.getElementById('calc-members-cost');
    var modulesCountEl = document.getElementById('calc-modules-count');
    var blocksCountEl = document.getElementById('calc-blocks-count');
    var modulesCostEl = document.getElementById('calc-modules-cost');
    var discountRowEl = document.getElementById('calc-discount-row');
    var discountAmountEl = document.getElementById('calc-discount-amount');
    var totalEl = document.getElementById('calc-total');
    var monthlyEl = document.getElementById('calc-monthly');

    var BASE_PRICE = 990;
    var INCLUDED_MEMBERS = 100;
    var PER_MEMBER_PRICE = 20;
    var MEMBER_BLOCK = 100;
    var ALL_MODULES_DISCOUNT = 20; // percent

    var formatter = new Intl.NumberFormat('pl-PL');

    function fmt(v) { return formatter.format(v) + ' PLN'; }

    function calculate() {
        var rawValue = parseInt(slider.value, 10);
        var isEnterprise = (rawValue > 500);
        var memberCount = isEnterprise ? 500 : rawValue;

        // Display
        sliderValue.textContent = isEnterprise ? '500+' : memberCount;

        // Enterprise mode
        if (isEnterprise) {
            summaryCard.style.display = 'none';
            modulesCard.style.opacity = '0.4';
            modulesCard.style.pointerEvents = 'none';
            enterpriseCard.style.display = '';
            return;
        } else {
            summaryCard.style.display = '';
            modulesCard.style.opacity = '';
            modulesCard.style.pointerEvents = '';
            enterpriseCard.style.display = 'none';
        }

        var blocks = Math.ceil(memberCount / MEMBER_BLOCK);
        var extraMembers = Math.max(0, memberCount - INCLUDED_MEMBERS);
        var membersCost = extraMembers * PER_MEMBER_PRICE;

        membersRowEl.style.display = extraMembers > 0 ? '' : 'none';

        // Modules
        var checkboxes = document.querySelectorAll('.calc-module-cb');
        var paidModules = document.querySelectorAll('.calc-module');
        var selectedCount = 0;
        var moduleBaseSum = 0;

        checkboxes.forEach(function (cb) {
            if (cb.checked) {
                selectedCount++;
                moduleBaseSum += parseInt(cb.closest('.calc-module').dataset.price, 10);
            }
        });

        var moduleCost = moduleBaseSum * blocks;

        // Discount
        var discount = 0;
        var allSelected = (selectedCount === paidModules.length);
        if (allSelected && ALL_MODULES_DISCOUNT > 0) {
            discount = Math.round(moduleCost * ALL_MODULES_DISCOUNT / 100);
        }

        var total = BASE_PRICE + membersCost + moduleCost - discount;

        // Update UI
        baseCostEl.textContent = fmt(BASE_PRICE);
        extraCountEl.textContent = extraMembers;
        membersCostEl.textContent = fmt(membersCost);
        modulesCountEl.textContent = selectedCount;
        blocksCountEl.textContent = blocks;
        modulesCostEl.textContent = fmt(moduleCost);

        discountRowEl.style.display = (allSelected && discount > 0) ? '' : 'none';
        discountAmountEl.textContent = '-' + fmt(discount);

        totalEl.textContent = formatter.format(total);
        monthlyEl.textContent = formatter.format(Math.round(total / 12));
    }

    slider.addEventListener('input', calculate);

    document.getElementById('calc-modules').addEventListener('change', function (e) {
        if (e.target.classList.contains('calc-module-cb')) calculate();
    });

    selectAllBtn.addEventListener('click', function () {
        var cbs = document.querySelectorAll('.calc-module-cb');
        var allChecked = true;
        cbs.forEach(function (cb) { if (!cb.checked) allChecked = false; });
        cbs.forEach(function (cb) { cb.checked = !allChecked; });
        calculate();
    });

    calculate();
})();
</script>
