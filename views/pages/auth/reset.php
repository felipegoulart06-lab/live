<section class="auth-card">
    <h1>Nova senha</h1>
    <form method="post" action="<?= e(url('/redefinir-senha/' . $token)) ?>" class="form">
        <?= csrf_field() ?>
        <label>Senha
            <input type="password" name="password" required minlength="8">
            <?php if ($msg = error_field('password')): ?><span class="field-err"><?= e($msg) ?></span><?php endif; ?>
        </label>
        <label>Confirmar senha
            <input type="password" name="password_confirmation" required minlength="8">
        </label>
        <button class="btn btn-accent btn-block" type="submit">Redefinir</button>
    </form>
</section>
