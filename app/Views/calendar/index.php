<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><i class="bi bi-calendar3"></i> Kalendarz klubowy</h2>
    <?php if ($canManage): ?>
    <a href="<?= url('calendar/events/create?date=' . date('Y-m-d')) ?>" class="btn btn-danger btn-sm">
        <i class="bi bi-plus-lg"></i> Dodaj wydarzenie
    </a>
    <?php endif; ?>
</div>

<!-- Navigation -->
<div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
    <a href="<?= url('calendar?year=' . $prevYear . '&month=' . $prevMonth) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h5 class="mb-0 fw-bold"><?= e($monthName) ?> <?= e($year) ?></h5>
    <a href="<?= url('calendar?year=' . $nextYear . '&month=' . $nextMonth) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-chevron-right"></i>
    </a>
    <a href="<?= url('calendar?year=' . date('Y') . '&month=' . date('n')) ?>" class="btn btn-outline-primary btn-sm">
        Dziś
    </a>

    <!-- Legend -->
    <div class="ms-auto d-flex gap-2 align-items-center flex-wrap small text-muted">
        <span><span class="badge bg-primary">●</span> Planowane</span>
        <span><span class="badge bg-success">●</span> Otwarte/Zakończone</span>
        <span><span class="badge bg-warning text-dark">●</span> Zamknięte</span>
        <span><span class="badge bg-secondary">●</span> Wydarzenie</span>
        <span><span class="badge bg-info">●</span> Zawody zewn.</span>
    </div>
</div>

<div class="card">
    <div class="card-body p-2">
        <table class="table table-bordered mb-0 calendar-table">
            <thead class="table-dark text-center">
                <tr>
                    <th>Pon</th><th>Wt</th><th>Śr</th><th>Czw</th><th>Pt</th>
                    <th class="text-warning">Sob</th>
                    <th class="text-warning">Nd</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $todayDay   = (int)date('j');
                $todayMonth = (int)date('n');
                $todayYear  = (int)date('Y');

                $cellNum       = 0;
                $day           = 1;
                $leadingBlanks = $startDow - 1;
                ?>
                <tr>
                <?php
                for ($b = 0; $b < $leadingBlanks; $b++) {
                    echo '<td class="bg-light cal-cell"></td>';
                    $cellNum++;
                }

                while ($day <= $daysInMonth):
                    $isToday   = ($day === $todayDay && $month === $todayMonth && $year === $todayYear);
                    $dayEvents = $events[$day] ?? [];
                    $dow       = ($leadingBlanks + $day - 1) % 7;
                    $isWeekend = ($dow >= 5);
                    $dateStr   = sprintf('%04d-%02d-%02d', $year, $month, $day);
                ?>
                <td class="align-top p-1 <?= $isWeekend ? 'table-light' : '' ?> cal-cell">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="<?= $isToday ? 'badge bg-danger rounded-circle' : 'text-muted small' ?>" style="min-width:1.5rem;text-align:center">
                            <?= $day ?>
                        </span>
                        <?php if ($canManage): ?>
                        <a href="<?= url('calendar/events/create?date=' . $dateStr) ?>"
                           class="text-muted cal-add-btn" title="Dodaj wydarzenie"
                           style="font-size:.75rem;text-decoration:none;opacity:0;transition:opacity .15s">+</a>
                        <?php endif; ?>
                    </div>

                    <?php foreach ($dayEvents as $ev):
                        $isCustom = ($ev['_source'] === 'custom');

                        if ($isCustom):
                            $color = e($ev['color'] ?? 'secondary');
                            $typeLabels = \App\Controllers\CalendarController::typeLabels();
                            $typeLabel  = $typeLabels[$ev['type'] ?? ''] ?? $ev['type'] ?? '';
                    ?>
                    <div class="cal-event bg-<?= $color ?> bg-opacity-15 border border-<?= $color ?> rounded mb-1 px-1"
                         style="font-size:.72rem;line-height:1.3">
                        <div class="d-flex justify-content-between align-items-start gap-1">
                            <span class="fw-semibold text-<?= $color === 'warning' ? 'dark' : $color ?>">
                                <?= e(mb_strimwidth($ev['title'], 0, 28, '…')) ?>
                            </span>
                            <?php if ($canManage): ?>
                            <span class="d-flex gap-1 flex-shrink-0">
                                <a href="<?= url('calendar/events/' . $ev['id'] . '/edit') ?>"
                                   class="text-muted" title="Edytuj" style="font-size:.7rem">✏️</a>
                                <form method="post" action="<?= url('calendar/events/' . $ev['id'] . '/delete') ?>"
                                      class="d-inline" onsubmit="return confirm('Usunąć to wydarzenie?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-link p-0 text-muted" title="Usuń" style="font-size:.7rem;line-height:1">🗑️</button>
                                </form>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($ev['location'])): ?>
                        <div class="text-muted" style="font-size:.65rem"><i class="bi bi-geo-alt"></i> <?= e($ev['location']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($ev['url'])): ?>
                        <a href="<?= e($ev['url']) ?>" target="_blank" rel="noopener"
                           class="text-<?= $color ?>" style="font-size:.65rem">
                            <i class="bi bi-link-45deg"></i> Link
                        </a>
                        <?php endif; ?>
                    </div>

                    <?php else:
                        // System competition
                        $sc = match($ev['status'] ?? '') {
                            'planowane'  => 'primary',
                            'otwarte'    => 'success',
                            'zakonczone' => 'success',
                            'zamkniete'  => 'warning',
                            default      => 'secondary',
                        };
                    ?>
                    <a href="<?= url('competitions/' . $ev['id']) ?>"
                       class="d-block badge bg-<?= $sc ?> text-wrap text-start mb-1 text-decoration-none"
                       style="font-size:.72rem;white-space:normal;line-height:1.2"
                       title="<?= e($ev['name']) ?> — <?= e($ev['discipline_name'] ?? '') ?>">
                        <i class="bi bi-trophy-fill" style="font-size:.6rem"></i>
                        <?= e(mb_strimwidth($ev['name'], 0, 28, '…')) ?>
                    </a>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </td>
                <?php
                    $cellNum++;
                    $day++;
                    if ($cellNum % 7 === 0 && $day <= $daysInMonth) {
                        echo '</tr><tr>';
                    }
                endwhile;

                $remaining = (7 - ($cellNum % 7)) % 7;
                for ($b = 0; $b < $remaining; $b++) {
                    echo '<td class="bg-light cal-cell"></td>';
                }
                ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.calendar-table { table-layout: fixed; }
.calendar-table td.cal-cell { vertical-align: top; min-height: 100px; height: 100px; width: 14.28%; }
.cal-cell:hover .cal-add-btn { opacity: .5 !important; }
.cal-event { word-break: break-word; }
</style>

<script>
// Hover effect: show + button only on cell hover
document.querySelectorAll('.cal-cell').forEach(function(cell) {
    cell.addEventListener('mouseenter', function() {
        var btn = cell.querySelector('.cal-add-btn');
        if (btn) btn.style.opacity = '.6';
    });
    cell.addEventListener('mouseleave', function() {
        var btn = cell.querySelector('.cal-add-btn');
        if (btn) btn.style.opacity = '0';
    });
});
</script>
