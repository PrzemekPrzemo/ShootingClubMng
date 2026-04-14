<h2 class="mb-4"><i class="bi bi-calculator"></i> Kalkulator cen</h2>
<p class="text-muted mb-4">Oszacuj koszt subskrypcji dla nowego klubu. Pokazuje pierwszy rok (z wdrożeniem) oraz kolejne lata.</p>

<div class="row g-4">
    <!-- Left: Controls -->
    <div class="col-lg-7">
        <!-- Member slider -->
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-people me-1"></i> Liczba członków</h6></div>
            <div class="card-body">
                <input type="range" class="form-range" id="calc-members" min="10" max="1200" value="200" step="10">
                <div class="d-flex justify-content-between">
                    <small class="text-muted">10</small>
                    <strong class="fs-5" id="calc-members-value">200</strong>
                    <small class="text-muted">600+</small>
                </div>
                <p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle"></i> Opłata bazowa obejmuje 200 członków. Każde kolejne 200 = 500 PLN/mies.</p>
            </div>
        </div>

        <!-- Modules -->
        <div class="card" id="calc-modules-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-puzzle me-1"></i> Moduły dodatkowe</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="calc-select-all">Zaznacz wszystkie</button>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Ceny modułów płatnych naliczane za każde rozpoczęte 200 członków / rok</p>
                <div class="row g-2" id="calc-modules">
                    <!-- Core included -->
                    <div class="col-md-6"><label class="d-flex align-items-center gap-2 p-2 rounded border bg-body-tertiary opacity-75"><input type="checkbox" class="form-check-input" checked disabled><i class="bi bi-people text-primary"></i><span class="flex-grow-1">Zarządzanie członkami</span><span class="badge bg-secondary">W cenie</span></label></div>
                    <div class="col-md-6"><label class="d-flex align-items-center gap-2 p-2 rounded border bg-body-tertiary opacity-75"><input type="checkbox" class="form-check-input" checked disabled><i class="bi bi-card-checklist text-primary"></i><span class="flex-grow-1">Licencje PZSS</span><span class="badge bg-secondary">W cenie</span></label></div>
                    <div class="col-md-6"><label class="d-flex align-items-center gap-2 p-2 rounded border bg-body-tertiary opacity-75"><input type="checkbox" class="form-check-input" checked disabled><i class="bi bi-heart-pulse text-primary"></i><span class="flex-grow-1">Badania lekarskie</span><span class="badge bg-secondary">W cenie</span></label></div>
                    <div class="col-md-6"><label class="d-flex align-items-center gap-2 p-2 rounded border bg-body-tertiary opacity-75"><input type="checkbox" class="form-check-input" checked disabled><i class="bi bi-gear text-primary"></i><span class="flex-grow-1">Konfiguracja</span><span class="badge bg-secondary">W cenie</span></label></div>
                    <div class="col-md-6"><label class="d-flex align-items-center gap-2 p-2 rounded border bg-body-tertiary opacity-75"><input type="checkbox" class="form-check-input" checked disabled><i class="bi bi-calendar3 text-primary"></i><span class="flex-grow-1">Kalendarz i wydarzenia</span><span class="badge bg-secondary">W cenie</span></label></div>
                    <div class="col-md-6"><label class="d-flex align-items-center gap-2 p-2 rounded border bg-body-tertiary opacity-75"><input type="checkbox" class="form-check-input" checked disabled><i class="bi bi-megaphone text-primary"></i><span class="flex-grow-1">Ogłoszenia</span><span class="badge bg-secondary">W cenie</span></label></div>
                    <div class="col-md-6"><label class="d-flex align-items-center gap-2 p-2 rounded border bg-body-tertiary opacity-75"><input type="checkbox" class="form-check-input" checked disabled><i class="bi bi-shield-check text-primary"></i><span class="flex-grow-1">Dashboard bezpieczeństwa</span><span class="badge bg-success">GRATIS</span></label></div>

                    <!-- Paid scaled (× 200-member blocks) -->
                    <div class="col-md-6"><label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-price="290"><input type="checkbox" class="form-check-input calc-module-cb"><i class="bi bi-cash-stack text-warning"></i><span class="flex-grow-1">Finanse i składki</span><span class="text-muted small">290 PLN / 200 czł.</span></label></div>
                    <div class="col-md-6"><label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-price="390"><input type="checkbox" class="form-check-input calc-module-cb"><i class="bi bi-trophy text-warning"></i><span class="flex-grow-1">Zawody i wyniki</span><span class="text-muted small">390 PLN / 200 czł.</span></label></div>
                    <div class="col-md-6"><label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-price="190"><input type="checkbox" class="form-check-input calc-module-cb"><i class="bi bi-tools text-warning"></i><span class="flex-grow-1">Sprzęt i broń</span><span class="text-muted small">190 PLN / 200 czł.</span></label></div>
                    <div class="col-md-6"><label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-price="190"><input type="checkbox" class="form-check-input calc-module-cb"><i class="bi bi-calendar-event text-warning"></i><span class="flex-grow-1">Treningi i obecność</span><span class="text-muted small">190 PLN / 200 czł.</span></label></div>
                    <div class="col-md-6"><label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-price="290"><input type="checkbox" class="form-check-input calc-module-cb"><i class="bi bi-person-workspace text-warning"></i><span class="flex-grow-1">Portal członka</span><span class="text-muted small">290 PLN / 200 czł.</span></label></div>
                    <div class="col-md-6"><label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-price="190"><input type="checkbox" class="form-check-input calc-module-cb"><i class="bi bi-file-earmark-bar-graph text-warning"></i><span class="flex-grow-1">Raporty i analizy</span><span class="text-muted small">190 PLN / 200 czł.</span></label></div>
                    <div class="col-md-6"><label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-price="90"><input type="checkbox" class="form-check-input calc-module-cb"><i class="bi bi-bank text-warning"></i><span class="flex-grow-1">Opłaty federacyjne</span><span class="text-muted small">90 PLN / 200 czł.</span></label></div>

                    <!-- Flat modules (one-time setup + optional yearly) -->
                    <div class="col-md-12"><label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-setup="500" data-yearly="0" data-flat="1"><input type="checkbox" class="form-check-input calc-module-cb"><i class="bi bi-chat-dots text-info"></i><span class="flex-grow-1">Komunikacja SMS do klubowiczów</span><span class="text-muted small">500 PLN wdrożenie <em>jednorazowo</em> + 0,05 PLN/SMS</span></label></div>
                    <div class="col-md-12"><label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-setup="1000" data-yearly="1200" data-flat="1"><input type="checkbox" class="form-check-input calc-module-cb"><i class="bi bi-envelope-at text-info"></i><span class="flex-grow-1">Powiadomienia email (hostowane)</span><span class="text-muted small">1000 PLN wdrożenie + 100 PLN/mies. (1200 PLN/rok)</span></label></div>
                    <div class="col-md-12"><label class="d-flex align-items-center gap-2 p-2 rounded border calc-module" data-setup="1000" data-yearly="0" data-flat="1"><input type="checkbox" class="form-check-input calc-module-cb"><i class="bi bi-envelope-gear text-info"></i><span class="flex-grow-1">Email SMTP klubu (wysyłka z własnego konta)</span><span class="text-muted small">1000 PLN konfiguracja, bez opłat miesięcznych</span></label></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Summary -->
    <div class="col-lg-5">
        <div class="card border-primary sticky-top" style="top: 80px;" id="calc-summary">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-receipt me-1"></i> Szacowany koszt</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-3">
                    <tbody>
                        <tr>
                            <td>Opłata bazowa <small class="text-muted">(wdrożenie, szkolenie, do 200 czł.) — jednorazowo</small></td>
                            <td class="text-end fw-bold" id="calc-base-cost">3 000 PLN</td>
                        </tr>
                        <tr id="calc-members-row" style="display: none;">
                            <td>Dodatkowe bloki po 200 czł. <small class="text-muted">(<span id="calc-extra-blocks">0</span> × 500 PLN/mies.)</small></td>
                            <td class="text-end" id="calc-members-cost">0 PLN</td>
                        </tr>
                        <tr>
                            <td>Moduły <small class="text-muted">(<span id="calc-modules-count">0</span>) × <span id="calc-blocks-count">1</span> blok(ów)</small></td>
                            <td class="text-end" id="calc-modules-cost">0 PLN</td>
                        </tr>
                        <tr id="calc-discount-row" style="display: none;">
                            <td class="text-success"><i class="bi bi-tag me-1"></i>Rabat za wszystkie moduły (-20%)</td>
                            <td class="text-end text-success fw-bold" id="calc-discount-amount">-0 PLN</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <td class="fw-bold">Pierwszy rok</td>
                            <td class="text-end fw-bold fs-5"><span id="calc-total-year1">3 000</span> PLN</td>
                        </tr>
                        <tr class="table-secondary">
                            <td class="small">Kolejne lata</td>
                            <td class="text-end small"><span id="calc-total-year2">0</span> PLN / rok</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Miesięcznie (rok 2+)</td>
                            <td class="text-end text-muted small"><span id="calc-monthly">0</span> PLN / mies.</td>
                        </tr>
                    </tfoot>
                </table>
                <p class="text-muted small mb-0"><i class="bi bi-info-circle"></i> Opłata bazowa 3 000 PLN (wdrożenie + szkolenie) jest jednorazowa — naliczana tylko w pierwszym roku.</p>
            </div>
        </div>

        <div class="card border-warning mt-3" id="calc-enterprise" style="display: none;">
            <div class="card-body text-center py-4">
                <i class="bi bi-building fs-1 text-warning mb-2 d-block"></i>
                <h5>Powyżej 600 członków</h5>
                <p class="text-muted mb-0">Negocjuj własną cenę — dopasujemy ofertę do skali i potrzeb klubu.</p>
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
    var extraBlocksEl = document.getElementById('calc-extra-blocks');
    var membersCostEl = document.getElementById('calc-members-cost');
    var modulesCountEl = document.getElementById('calc-modules-count');
    var blocksCountEl = document.getElementById('calc-blocks-count');
    var modulesCostEl = document.getElementById('calc-modules-cost');
    var discountRowEl = document.getElementById('calc-discount-row');
    var discountAmountEl = document.getElementById('calc-discount-amount');
    var totalY1El = document.getElementById('calc-total-year1');
    var totalY2El = document.getElementById('calc-total-year2');
    var monthlyEl = document.getElementById('calc-monthly');

    var BASE_SETUP = 3000;
    var INCLUDED_MEMBERS = 200;
    var EXTRA_BLOCK_MONTHLY = 500;
    var MEMBER_BLOCK = 200;
    var ENTERPRISE_THRESHOLD = 600;
    var ALL_MODULES_DISCOUNT = 20;

    var formatter = new Intl.NumberFormat('pl-PL');
    function fmt(v) { return formatter.format(v) + ' PLN'; }

    function calculate() {
        var rawValue = parseInt(slider.value, 10);
        var isEnterprise = (rawValue > ENTERPRISE_THRESHOLD);
        var memberCount = isEnterprise ? ENTERPRISE_THRESHOLD : rawValue;

        sliderValue.textContent = isEnterprise ? '600+' : memberCount;

        if (isEnterprise) {
            summaryCard.style.display = 'none';
            modulesCard.style.display = 'none';
            enterpriseCard.style.display = '';
            return;
        }
        summaryCard.style.display = '';
        modulesCard.style.display = '';
        enterpriseCard.style.display = 'none';

        var totalBlocks = Math.ceil(memberCount / MEMBER_BLOCK);
        var extraBlocks = Math.max(0, totalBlocks - 1);
        var membersCostYearly = extraBlocks * EXTRA_BLOCK_MONTHLY * 12;

        membersRowEl.style.display = extraBlocks > 0 ? '' : 'none';

        var cbs = document.querySelectorAll('.calc-module-cb');
        var paidModules = document.querySelectorAll('.calc-module');
        var selectedCount = 0;
        var scaledSum = 0;
        var flatSetup = 0;
        var flatYearly = 0;

        cbs.forEach(function (cb) {
            if (!cb.checked) return;
            selectedCount++;
            var lbl = cb.closest('.calc-module');
            if (lbl.dataset.flat === '1') {
                flatSetup += parseInt(lbl.dataset.setup || '0', 10);
                flatYearly += parseInt(lbl.dataset.yearly || '0', 10);
            } else {
                scaledSum += parseInt(lbl.dataset.price || '0', 10);
            }
        });

        var scaledYearly = scaledSum * totalBlocks;
        var modulesDisplay = scaledYearly + flatSetup + flatYearly;

        var discount = 0;
        var allSelected = (selectedCount === paidModules.length);
        if (allSelected && ALL_MODULES_DISCOUNT > 0) {
            discount = Math.round(scaledYearly * ALL_MODULES_DISCOUNT / 100);
        }

        var year1 = BASE_SETUP + membersCostYearly + scaledYearly + flatSetup + flatYearly - discount;
        var year2 = membersCostYearly + scaledYearly + flatYearly - discount;

        baseCostEl.textContent = fmt(BASE_SETUP);
        extraBlocksEl.textContent = extraBlocks;
        membersCostEl.textContent = fmt(membersCostYearly);
        modulesCountEl.textContent = selectedCount;
        blocksCountEl.textContent = totalBlocks;
        modulesCostEl.textContent = fmt(modulesDisplay);

        discountRowEl.style.display = (allSelected && discount > 0) ? '' : 'none';
        discountAmountEl.textContent = '-' + fmt(discount);

        totalY1El.textContent = formatter.format(year1);
        totalY2El.textContent = formatter.format(year2);
        monthlyEl.textContent = formatter.format(Math.round(year2 / 12));
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
