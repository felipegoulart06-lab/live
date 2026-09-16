<section class="dash-page">
    <div class="section-head">
        <div>
            <h1><?= e($account['display_name'] ?: $account['email']) ?></h1>
            <p class="muted"><?= e($account['email']) ?> · criado em <?= e($account['created_at']) ?></p>
        </div>
        <a class="btn btn-ghost" href="<?= e(url('/admin/usuarios')) ?>">Voltar</a>
    </div>
    <form class="admin-form" method="post" action="<?= e(url('/admin/usuarios/' . $account['id'])) ?>">
        <?= csrf_field() ?>
        <fieldset>
            <legend>Perfil</legend>
            <div class="form-grid">
                <label>Nome
                    <input name="display_name" value="<?= e(old('display_name', $account['display_name'] ?? '')) ?>" required>
                </label>
                <label>Telefone
                    <input name="phone" value="<?= e(old('phone', $account['phone'] ?? '')) ?>">
                </label>
                <label>Cidade
                    <input name="city" value="<?= e(old('city', $account['city'] ?? '')) ?>">
                </label>
                <label>UF
                    <input name="state" maxlength="2" value="<?= e(old('state', $account['state'] ?? '')) ?>">
                </label>
            </div>
            <label class="check"><input type="checkbox" name="is_verified" value="1" <?= !empty($account['is_verified']) ? 'checked' : '' ?>> Conta verificada</label>
        </fieldset>
        <fieldset>
            <legend>Acesso</legend>
            <label>Status
                <select name="status">
                    <?php foreach (['active' => 'Ativo', 'pending' => 'Pendente', 'suspended' => 'Suspenso', 'banned' => 'Banido'] as $k => $label): ?>
                        <option value="<?= e($k) ?>" <?= ($account['status'] === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <p class="chip-label">Papéis</p>
            <div class="checks">
                <?php foreach ($roles as $role): ?>
                    <?php if ($role['slug'] === 'super_admin' && !($authUser && $authUser->hasRole('super_admin'))): continue; endif; ?>
                    <label class="check">
                        <input type="checkbox" name="roles[]" value="<?= e($role['slug']) ?>" <?= in_array($role['slug'], $roleSlugs, true) ? 'checked' : '' ?>>
                        <?= e($role['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <div class="form-actions">
            <button class="btn btn-accent" type="submit">Salvar conta</button>
        </div>
    </form>
</section>
