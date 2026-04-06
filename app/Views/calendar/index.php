<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><i class="bi bi-calendar3"></i> Kalendarz zawodów</h2>
</div>

<!-- Navigation -->
<div class="d-flex align-items-center gap-3 mb-3">
    <a href="<?= url('calendar?year=' . $prevYear . '&month=' . $prevMonth) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h5 class="mb-0 fw-bold"><?= e($monthName) ?> <?= e($year) ?></h5>
    <a href="<?= url('calendar?year=' . $nextYear . '&month=' . $nextMonth) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-chevron-right"></i>
    </a>
    <a href="<?= url('calendar?year=' . date('Y') . '&month=' . date('n')) ?>" class="btn btn-outline-primary btn-sm ms-2">
        Dziś
    </a>
    <div class="ms-auto d-flex gap-2 align-items-center small text-muted">
        <span class="badge bg-primary">planowane</span>
        <span class="badge bg-success">otwarte / zakończone</span>
        <span class="badge bg-warning text-dark">zamknięte</span>
    </div>
</div>

<div class="card">
    <div class="card-body p-2">
        <table class="table table-bordered mb-0 calendar-table">
            <thead class="table-dark text-center">
                <tr>
                    <th>Pon</th>
                    <th>Wt</th>
                    <th>Śr</th>
                    <th>Czw</th>
                    <th>Pt</th>
                    <th class="text-warning">Sob</th>
                    <th class="text-warning">Nd</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $todayDay   = (int)date('j');
                $todayMonth = (int)date('n');
                $todayYear  = (int)date('Y');

                $cellNum = 0;
                $day     = 1;
                // Number of blank cells before day 1 (Mon=0 blanks, Tue=1 blank, etc.)
                $leadingBlanks = $startDow - 1;
                ?>
                <tr>
                <?php
                // Leading blanks
                for ($b = 0; $b < $leadingBlanks; $b++) {
                    echo '<td class="bg-light" style="min-height:90px;height:90px"></td>';
                    $cellNum++;
                }

                while ($day <= $daysInMonth):
                    $isToday   = ($day === $todayDay && $month === $todayMonth && $year === $todayYear);
                    $dayEvents = $events[$day] ?? [];
                    $dow       = ($leadingBlanks + $day - 1) % 7; // 0=Mon … 6=Sun
                    $isWeekend = ($dow >= 5);
                ?>
                    <td class="align-top p-1 <?= $isWeekend ? 'table-light' : '' ?>" style="min-height:90px;height:90px;width:14.28%">
                        <div class="d-flex justify-content-end">
                            <span class="<?= $isToday ? 'badge bg-danger rounded-circle' : 'text-muted small' ?>" style="min-width:1.5rem;text-align:center">
                                <?= $day ?>
                            </span>
                        </div>
                        <?php foreach ($dayEvents as $ev):
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
                            <?= e(mb_strimwidth($ev['name'], 0, 30, '…')) ?>
                        </a>
                        <?php endforeach; ?>
                    </td>
                <?php
                    $cellNum++;
                    $day++;
                    // End of week — close row and start new
                    if ($cellNum % 7 === 0 && $day <= $daysInMonth) {
                        echo '</tr><tr>';
                    }
                endwhile;

                // Trailing blanks to complete last row
                $remaining = (7 - ($cellNum % 7)) % 7;
                for ($b = 0; $b < $remaining; $b++) {
                    echo '<td class="bg-light" style="min-height:90px;height:90px"></td>';
                }
                ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.calendar-table td { vertical-align: top; }
.calendar-table { table-layout: fixed; }
</style>
