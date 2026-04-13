<?php $isEdit = !empty($club); ?>
<h2 class="mb-4">
    <i class="bi bi-building"></i>
    <?= $isEdit ? 'Edycja klubu' : 'Nowy klub' ?>
</h2>

<div class="card" style="max-width:700px">
    <div class="card-body">
        <form method="post" action="<?= $isEdit ? url("admin/clubs/{$club['id']}/edit") : url('admin/clubs/create') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="name" class="form-label">Nazwa klubu *</label>
                <input type="text" class="form-control" id="name" name="name"
                       value="<?= e($club['name'] ?? '') ?>" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="short_name" class="form-label">Skrót</label>
                    <input type="text" class="form-control" id="short_name" name="short_name"
                           value="<?= e($club['short_name'] ?? '') ?>" maxlength="50">
                </div>
                <div class="col-md-8">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= e($club['email'] ?? '') ?>">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="phone" class="form-label">Telefon</label>
                    <input type="text" class="form-control" id="phone" name="phone"
                           value="<?= e($club['phone'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label for="nip" class="form-label">NIP</label>
                    <input type="text" class="form-control" id="nip" name="nip"
                           value="<?= e($club['nip'] ?? '') ?>" maxlength="15">
                </div>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Adres</label>
                <textarea class="form-control" id="address" name="address" rows="2"><?= e($club['address'] ?? '') ?></textarea>
            </div>

            <?php if ($isEdit): ?>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                       <?= $club['is_active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Aktywny</label>
            </div>
            <?php endif; ?>

            <hr class="my-4">
            <h6 class="text-uppercase text-muted small mb-3"><i class="bi bi-credit-card-2-front"></i> Subskrypcja / Limity</h6>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Plan</label>
                    <select class="form-select" name="plan">
                        <option value="">— Brak —</option>
                        <?php foreach (['trial' => 'Trial (próbny)', 'basic' => 'Basic', 'standard' => 'Standard', 'premium' => 'Premium'] as $planKey => $planLabel): ?>
                        <option value="<?= e($planKey) ?>" <?= ($subscription['plan'] ?? '') === $planKey ? 'selected' : '' ?>>
                            <?= e($planLabel) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ważny do</label>
                    <input type="date" class="form-control" name="valid_until"
                           value="<?= e($subscription['valid_until'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="sub_status">
                        <option value="active"    <?= ($subscription['status'] ?? 'active') === 'active'    ? 'selected' : '' ?>>Aktywny</option>
                        <option value="expired"   <?= ($subscription['status'] ?? '')        === 'expired'   ? 'selected' : '' ?>>Wygasły</option>
                        <option value="cancelled" <?= ($subscription['status'] ?? '')        === 'cancelled' ? 'selected' : '' ?>>Anulowany</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Maksymalna liczba zawodników</label>
                <input type="number" class="form-control" name="max_members" min="1" style="max-width:200px"
                       value="<?= e($subscription['max_members'] ?? '') ?>"
                       placeholder="bez limitu">
                <div class="form-text">Pozostaw puste = bez limitu.</div>
            </div>

            <hr class="my-4">
            <h6 class="text-uppercase text-muted small mb-1"><i class="bi bi-toggles"></i> Aktywne moduły</h6>
            <p class="text-muted small mb-3">Odznaczone moduły nie będą widoczne w menu bocznym użytkowników tego klubu.</p>

            <div class="row g-2 mb-4">
                <?php foreach (\App\Models\RolePermissionModel::MODULES as $mod => $cfg):
                    if ($mod === 'dashboard') continue; ?>
                <div class="col-md-4 col-lg-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="modules[]"
                               value="<?= e($mod) ?>" id="mod_<?= e($mod) ?>"
                               <?= ($clubModules[$mod] ?? true) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="mod_<?= e($mod) ?>">
                            <i class="bi bi-<?= e($cfg['icon']) ?>"></i> <?= e($cfg['label']) ?>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <hr class="my-4">
            <h6 class="text-uppercase text-muted small mb-3"><i class="bi bi-envelope-gear"></i> Konfiguracja SMTP</h6>
            <p class="text-muted small mb-2">Zostaw wyłączone, aby używać globalnego SMTP systemu.</p>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="smtp_enabled" name="smtp_enabled"
                       <?= !empty($smtpConfig['smtp_enabled']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="smtp_enabled">Własny serwer SMTP dla tego klubu</label>
            </div>

            <div id="smtp_fields" <?= empty($smtpConfig['smtp_enabled']) ? 'style="display:none"' : '' ?>>
                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="form-label">Serwer SMTP</label>
                        <input type="text" class="form-control" name="smtp_host"
                               value="<?= e($smtpConfig['smtp_host'] ?? '') ?>" placeholder="smtp.example.com">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Port</label>
                        <input type="number" class="form-control" name="smtp_port"
                               value="<?= (int)($smtpConfig['smtp_port'] ?? 587) ?>" min="1" max="65535">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Szyfrowanie</label>
                        <select class="form-select" name="smtp_secure">
                            <?php foreach (['tls' => 'TLS (STARTTLS)', 'ssl' => 'SSL', '' => 'Brak'] as $val => $lbl): ?>
                            <option value="<?= e($val) ?>" <?= ($smtpConfig['smtp_secure'] ?? 'tls') === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Użytkownik SMTP</label>
                        <input type="text" class="form-control" name="smtp_user"
                               value="<?= e($smtpConfig['smtp_user'] ?? '') ?>" autocomplete="off">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Hasło SMTP</label>
                    <input type="password" class="form-control" name="smtp_pass_enc"
                           autocomplete="new-password"
                           placeholder="<?= $isEdit && !empty($smtpConfig['smtp_has_pass']) ? 'Zostaw puste = bez zmian' : 'Hasło' ?>">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nadawca — adres e-mail</label>
                        <input type="email" class="form-control" name="smtp_from_email"
                               value="<?= e($smtpConfig['smtp_from_email'] ?? '') ?>" placeholder="noreply@klub.pl">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nadawca — nazwa</label>
                        <input type="text" class="form-control" name="smtp_from_name"
                               value="<?= e($smtpConfig['smtp_from_name'] ?? '') ?>" placeholder="Nazwa Klubu">
                    </div>
                </div>
            </div>

            <script>
            document.getElementById('smtp_enabled').addEventListener('change', function () {
                document.getElementById('smtp_fields').style.display = this.checked ? '' : 'none';
            });
            </script>

            <hr class="my-4">
            <h6 class="text-uppercase text-muted small mb-3"><i class="bi bi-credit-card-2-front"></i> Przelewy24 — płatności online</h6>
            <p class="text-muted small mb-2">
                Pozwala zawodnikom opłacać składki i opłaty startowe bezpośrednio przez portal.
                Dane uzyskasz po rejestracji konta w
                <a href="https://www.przelewy24.pl" target="_blank" rel="noopener">przelewy24.pl</a>.
            </p>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="p24_enabled" name="p24_enabled"
                       <?= !empty($p24Config['p24_enabled']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="p24_enabled">Włącz płatności Przelewy24 dla tego klubu</label>
            </div>

            <div id="p24_fields" <?= empty($p24Config['p24_enabled']) ? 'style="display:none"' : '' ?>>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Merchant ID <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="p24_merchant_id"
                               value="<?= (int)($p24Config['p24_merchant_id'] ?? 0) ?: '' ?>"
                               placeholder="np. 123456">
                        <div class="form-text">Twoje ID sprzedawcy z panelu P24.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">POS ID</label>
                        <input type="number" class="form-control" name="p24_pos_id"
                               value="<?= (int)($p24Config['p24_pos_id'] ?? 0) ?: '' ?>"
                               placeholder="Domyślnie = Merchant ID">
                        <div class="form-text">Zostaw puste jeśli taki sam jak Merchant ID.</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Klucz API (api_key) <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="p24_api_key"
                               autocomplete="new-password"
                               placeholder="<?= $isEdit && !empty($p24Config['p24_has_api_key']) ? 'Zostaw puste = bez zmian' : 'Klucz API z panelu P24' ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Klucz CRC (crc_key) <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="p24_crc_key"
                               autocomplete="new-password"
                               placeholder="<?= $isEdit && !empty($p24Config['p24_has_crc_key']) ? 'Zostaw puste = bez zmian' : 'Klucz CRC z panelu P24' ?>">
                        <div class="form-text">Służy do weryfikacji webhooków.</div>
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="p24_sandbox" name="p24_sandbox"
                           <?= !empty($p24Config['p24_sandbox']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="p24_sandbox">
                        Tryb testowy (sandbox) — nie pobiera prawdziwych płatności
                    </label>
                </div>

                <?php if ($isEdit): ?>
                <div class="alert alert-info small py-2">
                    <i class="bi bi-info-circle me-1"></i>
                    URL powiadomień (wklej w panelu P24 → <em>Ustawienia → URL do powiadomień</em>):
                    <code class="ms-1 user-select-all"><?= url('portal/payment/notify') ?></code>
                </div>
                <?php endif; ?>
            </div>

            <script>
            document.getElementById('p24_enabled').addEventListener('change', function () {
                document.getElementById('p24_fields').style.display = this.checked ? '' : 'none';
            });
            </script>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Zapisz' : 'Utwórz' ?>
                </button>
                <a href="<?= url('admin/clubs') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>
