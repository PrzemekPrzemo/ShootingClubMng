<?php
$fieldLabels = [
    'first_name'            => 'Imię',
    'last_name'             => 'Nazwisko',
    'pesel'                 => 'PESEL',
    'birth_date'            => 'Data urodzenia',
    'gender'                => 'Płeć',
    'age_category_id'       => 'Kategoria wiekowa',
    'member_class_id'       => 'Klasa zawodnika',
    'member_type'           => 'Typ członkostwa',
    'card_number'           => 'Nr karty dostępu',
    'email'                 => 'E-mail',
    'phone'                 => 'Telefon',
    'address_street'        => 'Ulica',
    'address_city'          => 'Miejscowość',
    'address_postal'        => 'Kod pocztowy',
    'join_date'             => 'Data wstąpienia',
    'status'                => 'Status',
    'notes'                 => 'Uwagi',
    'firearm_permit_number' => 'Nr pozwolenia na broń',
    'photo_path'            => 'Zdjęcie',
];

$actionLabels = [
    'member_create'        => ['label' => 'Dodanie zawodnika', 'badge' => 'success'],
    'member_update'        => ['label' => 'Edycja profilu',    'badge' => 'primary'],
    'member_status_change' => ['label' => 'Zmiana statusu',   'badge' => 'warning'],
];
?>
<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('members/' . (int)$member['id']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0">
        <i class="bi bi-clock-history"></i>
        Historia zmian — <?= e($member['last_name']) ?> <?= e($member['first_name']) ?>
    </h2>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($entries)): ?>
        <p class="text-muted p-3 mb-0">Brak zapisanych zmian dla tego zawodnika.</p>
        <?php else: ?>
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-dark">
                <tr>
                    <th style="width:12rem">Data / godzina</th>
                    <th style="width:12rem">Użytkownik</th>
                    <th style="width:10rem">Akcja</th>
                    <th>Szczegóły</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry):
                    $details = [];
                    if (!empty($entry['details'])) {
                        $details = json_decode($entry['details'], true) ?: [];
                    }
                    $act   = $actionLabels[$entry['action']] ?? ['label' => e($entry['action']), 'badge' => 'secondary'];
                    $badge = $act['badge'];
                ?>
                <tr>
                    <td class="text-muted">
                        <i class="bi bi-calendar3 me-1"></i>
                        <?= e(date('d.m.Y H:i', strtotime($entry['created_at']))) ?>
                    </td>
                    <td>
                        <?php if (!empty($entry['user_name'])): ?>
                        <i class="bi bi-person me-1"></i><?= e($entry['user_name']) ?>
                        <?php else: ?>
                        <span class="text-muted">System</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $badge ?>"><?= $act['label'] ?></span>
                    </td>
                    <td>
                        <?php if ($entry['action'] === 'member_create'): ?>
                        <span class="text-success"><i class="bi bi-person-plus"></i> Zawodnik został zarejestrowany w systemie.</span>

                        <?php elseif ($entry['action'] === 'member_status_change'): ?>
                        <?php
                            $old = $details['old'] ?? null;
                            $new = $details['new'] ?? $details['new'] ?? null;
                            $act2 = $details['action'] ?? null;
                        ?>
                        <span class="text-warning">
                            <i class="bi bi-arrow-right-circle"></i>
                            Status:
                            <strong><?= $old ? e($old) : '—' ?></strong>
                            →
                            <strong><?= $new ? e($new) : '—' ?></strong>
                            <?php if ($act2 === 'wykreslenie'): ?>(wykreślenie)<?php endif; ?>
                        </span>

                        <?php elseif ($entry['action'] === 'member_update' && !empty($details['changed'])): ?>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($details['changed'] as $change):
                                $fieldLabel = $fieldLabels[$change['field']] ?? $change['field'];
                                $oldVal = $change['old'] ?? null;
                                $newVal = $change['new'] ?? null;
                                // Skip photo path details — just show "zmieniono"
                                if ($change['field'] === 'photo_path') {
                                    echo '<li><span class="fw-semibold">' . e($fieldLabel) . '</span>: <em class="text-muted">zaktualizowano</em></li>';
                                    continue;
                                }
                            ?>
                            <li>
                                <span class="fw-semibold"><?= e($fieldLabel) ?></span>:
                                <span class="text-muted"><?= $oldVal !== null && $oldVal !== '' ? e($oldVal) : '<em>(puste)</em>' ?></span>
                                <i class="bi bi-arrow-right mx-1"></i>
                                <span class="text-dark"><?= $newVal !== null && $newVal !== '' ? e($newVal) : '<em>(puste)</em>' ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>

                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
