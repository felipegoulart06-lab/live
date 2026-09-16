<section class="auth-card">
    <h1>Entrar</h1>
    <p class="muted">Acesse pedidos, mensagens e sua carteira.</p>
    <?= \App\Core\View::component('google-login') ?>
    <form method="post" action="<?= e(url('/entrar')) ?>" class="form">
        <?= csrf_field() ?>
        <label>E-mail
            <input type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email">
            <?php if ($msg = error_field('email')): ?><span class="field-err"><?= e($msg) ?></span><?php endif; ?>
        </label>
        <label>Senha
            <input type="password" name="password" required autocomplete="current-password">
            <?php if ($msg = error_field('password')): ?><span class="field-err"><?= e($msg) ?></span><?php endif; ?>
        </label>
        <button class="btn btn-accent btn-block" type="submit">Entrar</button>
    </form>
    <p><a href="<?= e(url('/esqueci-senha')) ?>">Esqueci a senha</a></p>
    <p>Novo por aqui? <a href="<?= e(url('/criar-conta')) ?>">Criar conta</a></p>
</section>
