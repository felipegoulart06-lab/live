<section class="dash-page">
    <div class="section-head">
        <div>
            <h1>Configurações</h1>
            <p class="muted">Parâmetros da plataforma, autenticação e SEO.</p>
        </div>
    </div>
    <form class="admin-form" method="post" action="<?= e(url('/admin/configuracoes')) ?>">
        <?= csrf_field() ?>
        <fieldset>
            <legend>Geral</legend>
            <div class="form-grid">
                <label>Nome da plataforma<input name="platform_name" value="<?= e(setting('platform_name', 'CinquentaConto')) ?>" required></label>
                <label>E-mail de suporte<input name="support_email" value="<?= e(setting('support_email', '')) ?>"></label>
            </div>
            <label>Tagline<input name="tagline" value="<?= e(setting('tagline', '')) ?>"></label>
            <div class="form-grid">
                <label>Telefone<input name="support_phone" value="<?= e(setting('support_phone', '')) ?>"></label>
                <label>WhatsApp<input name="whatsapp" value="<?= e(setting('whatsapp', '')) ?>"></label>
            </div>
            <div class="checks">
                <label class="check"><input type="checkbox" name="registrations_open" value="1" <?= setting('registrations_open', '1') === '1' ? 'checked' : '' ?>> Cadastros abertos</label>
                <label class="check"><input type="checkbox" name="maintenance_mode" value="1" <?= setting('maintenance_mode', '0') === '1' ? 'checked' : '' ?>> Modo manutenção</label>
            </div>
        </fieldset>
        <fieldset>
            <legend>Login com Google</legend>
            <p class="muted">No Google Cloud, crie um ID OAuth e coloque esta URI de redirecionamento:</p>
            <p><code class="code-box"><?= e($googleRedirect) ?></code></p>
            <p class="muted"><?= !empty($googleReady) ? 'Google ativo: o botão aparece em Entrar e Criar conta.' : 'Preencha ID, segredo e ative para liberar o botão.' ?></p>
            <label class="check"><input type="checkbox" name="google_oauth_enabled" value="1" <?= setting('google_oauth_enabled', '0') === '1' ? 'checked' : '' ?>> Ativar login com Google</label>
            <label>Client ID<input name="google_client_id" value="<?= e(setting('google_client_id', '')) ?>" autocomplete="off"></label>
            <label>Client secret
                <input type="password" name="google_client_secret" value="" placeholder="<?= setting('google_client_secret', '') !== '' ? '••••••••  (deixe em branco para manter)' : 'Cole o segredo' ?>" autocomplete="new-password">
            </label>
        </fieldset>
        <fieldset>
            <legend>SEO</legend>
            <label>Meta título<input name="meta_title" value="<?= e(setting('meta_title', '')) ?>"></label>
            <label>Meta descrição<textarea name="meta_description" rows="3"><?= e(setting('meta_description', '')) ?></textarea></label>
        </fieldset>
        <fieldset>
            <legend>Financeiro</legend>
            <div class="form-grid">
                <label>Comissão %<input name="commission_percent" value="<?= e(setting('commission_percent', '10')) ?>"></label>
                <label>Taxa fixa (centavos)<input name="fixed_fee_cents" value="<?= e(setting('fixed_fee_cents', '0')) ?>"></label>
                <label>Taxa de saque (centavos)<input name="withdraw_fee_cents" value="<?= e(setting('withdraw_fee_cents', '0')) ?>"></label>
                <label>Saque mínimo (centavos)<input name="min_withdraw_cents" value="<?= e(setting('min_withdraw_cents', '5000')) ?>"></label>
            </div>
        </fieldset>
        <fieldset>
            <legend>Social e analytics</legend>
            <div class="form-grid">
                <label>Instagram<input name="social_instagram" value="<?= e(setting('social_instagram', '')) ?>"></label>
                <label>LinkedIn<input name="social_linkedin" value="<?= e(setting('social_linkedin', '')) ?>"></label>
                <label>GA ID<input name="ga_id" value="<?= e(setting('ga_id', '')) ?>"></label>
                <label>GTM ID<input name="gtm_id" value="<?= e(setting('gtm_id', '')) ?>"></label>
            </div>
            <label>Meta Pixel<input name="meta_pixel" value="<?= e(setting('meta_pixel', '')) ?>"></label>
        </fieldset>
        <div class="form-actions"><button class="btn btn-accent" type="submit">Salvar configurações</button></div>
    </form>
</section>
