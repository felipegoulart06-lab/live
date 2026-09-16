<?php
$f = $freelancer ?? [];
$isEdit = $f !== [];
$val = static function (string $key, mixed $fallback = '') use ($f) {
    return old($key, $f[$key] ?? $fallback);
};
?>
<section class="dash-page">
    <div class="section-head">
        <div>
            <p class="eyebrow"><a href="<?= e(url('/admin/profissionais')) ?>">Profissionais</a></p>
            <h1><?= $isEdit ? 'Configurar profissional' : 'Adicionar profissional' ?></h1>
        </div>
        <?php if ($isEdit): ?>
            <a class="btn btn-ghost" href="<?= e(url('/admin/profissionais')) ?>">Voltar à lista</a>
        <?php endif; ?>
    </div>

    <form class="admin-form" method="post" action="<?= e(url($isEdit ? '/admin/profissionais/' . $f['id'] : '/admin/profissionais')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <fieldset>
            <legend>Conta</legend>
            <div class="form-grid">
                <label>Nome
                    <input type="text" name="display_name" required minlength="3" value="<?= e((string) $val('display_name')) ?>">
                    <?php if ($msg = error_field('display_name')): ?><span class="field-err"><?= e($msg) ?></span><?php endif; ?>
                </label>
                <label>Nome profissional
                    <input type="text" name="professional_name" value="<?= e((string) $val('professional_name')) ?>">
                </label>
                <label>E-mail
                    <input type="email" name="email" required value="<?= e((string) $val('email')) ?>">
                    <?php if ($msg = error_field('email')): ?><span class="field-err"><?= e($msg) ?></span><?php endif; ?>
                </label>
                <label><?= $isEdit ? 'Nova senha (opcional)' : 'Senha' ?>
                    <input type="password" name="password" <?= $isEdit ? '' : 'required minlength="8"' ?> autocomplete="new-password">
                    <?php if ($msg = error_field('password')): ?><span class="field-err"><?= e($msg) ?></span><?php endif; ?>
                </label>
                <label>Status
                    <select name="status" required>
                        <?php foreach (['active' => 'Ativo', 'pending' => 'Pendente', 'suspended' => 'Suspenso', 'banned' => 'Banido'] as $k => $label): ?>
                            <option value="<?= e($k) ?>" <?= ((string) $val('status', 'active') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php if ($isEdit): ?>
                    <label>Slug
                        <input type="text" name="slug" value="<?= e((string) $val('slug')) ?>">
                        <?php if ($msg = error_field('slug')): ?><span class="field-err"><?= e($msg) ?></span><?php endif; ?>
                    </label>
                <?php endif; ?>
            </div>
            <div class="checks">
                <?php
                $emailOk = old('email_verified', $f['email_verified_at'] ?? '') !== '' && old('email_verified', $f['email_verified_at'] ?? '') !== null;
                if (array_key_exists('email_verified', $GLOBALS['_nexo_old'] ?? [])) {
                    $emailOk = (string) old('email_verified') === '1';
                }
                $verifiedOk = (string) ($f['is_verified'] ?? '0') === '1';
                if (array_key_exists('is_verified', $GLOBALS['_nexo_old'] ?? [])) {
                    $verifiedOk = (string) old('is_verified') === '1';
                }
                $buyerOk = (($f['account_type'] ?? '') === 'both');
                if (array_key_exists('also_buyer', $GLOBALS['_nexo_old'] ?? [])) {
                    $buyerOk = (string) old('also_buyer') === '1';
                }
                ?>
                <label class="check"><input type="checkbox" name="email_verified" value="1" <?= $emailOk ? 'checked' : '' ?>> E-mail confirmado</label>
                <label class="check"><input type="checkbox" name="is_verified" value="1" <?= $verifiedOk ? 'checked' : '' ?>> Selo verificado</label>
                <label class="check"><input type="checkbox" name="also_buyer" value="1" <?= $buyerOk ? 'checked' : '' ?>> Também pode comprar na plataforma</label>
            </div>
        </fieldset>

        <fieldset>
            <legend>Perfil público</legend>
            <div class="form-grid">
                <label>Título / headline
                    <input type="text" name="headline" maxlength="180" value="<?= e((string) $val('headline')) ?>">
                </label>
                <label>Telefone
                    <input type="text" name="phone" value="<?= e((string) $val('phone')) ?>">
                </label>
                <label>Cidade
                    <input type="text" name="city" value="<?= e((string) $val('city')) ?>">
                </label>
                <label>Estado
                    <input type="text" name="state" value="<?= e((string) $val('state')) ?>">
                </label>
                <label>País
                    <input type="text" name="country" maxlength="2" value="<?= e((string) $val('country', 'BR')) ?>">
                </label>
                <label>Anos de experiência
                    <input type="number" name="experience_years" min="0" max="80" value="<?= e((string) $val('experience_years')) ?>">
                </label>
            </div>
            <label>Biografia
                <textarea name="bio" rows="5"><?= e((string) $val('bio')) ?></textarea>
            </label>
            <label>Habilidades (separadas por vírgula)
                <input type="text" name="skills" value="<?= e((string) old('skills', $skillsText)) ?>">
            </label>
            <label>Foto
                <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp">
                <?php if (!empty($f['avatar_path'])): ?>
                    <img class="avatar-img" src="<?= e(media($f['avatar_path'])) ?>" alt="" width="56" height="56">
                <?php endif; ?>
            </label>
        </fieldset>

        <fieldset>
            <legend>Comissão e selos</legend>
            <label>Comissão específica deste vendedor (%)
                <input type="number" name="commission_percent" step="0.01" min="0" max="100" placeholder="Padrão da plataforma" value="<?= e((string) old('commission_percent', $commissionPercent ?? '')) ?>">
            </label>
            <p class="chip-label">Selos</p>
            <div class="checks">
                <?php
                $posted = old('badge_ids');
                $selected = is_array($posted) ? array_map('intval', $posted) : $selectedBadges;
                foreach ($badges as $badge):
                ?>
                    <label class="check">
                        <input type="checkbox" name="badge_ids[]" value="<?= (int) $badge['id'] ?>" <?= in_array((int) $badge['id'], $selected, true) ? 'checked' : '' ?>>
                        <?= e($badge['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div class="form-actions">
            <button class="btn btn-accent" type="submit"><?= $isEdit ? 'Salvar configuração' : 'Cadastrar profissional' ?></button>
            <a class="btn btn-ghost" href="<?= e(url('/admin/profissionais')) ?>">Cancelar</a>
            <?php if ($isEdit && ($f['status'] ?? '') === 'active'): ?>
                <button class="btn btn-ghost" type="submit" form="deactivate-form" onclick="return confirm('Desativar este profissional?');">Desativar</button>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($isEdit): ?>
        <form id="deactivate-form" method="post" action="<?= e(url('/admin/profissionais/' . $f['id'] . '/desativar')) ?>" hidden>
            <?= csrf_field() ?>
        </form>
    <?php endif; ?>
</section>
