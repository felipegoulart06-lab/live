<section class="auth-card">
    <h1>Criar conta</h1>
    <p class="muted">Um cadastro. Depois você pode comprar e vender.</p>
    <?= \App\Core\View::component('google-login', ['intent' => ($intent ?? 'buyer') === 'seller' ? 'seller' : 'buyer']) ?>
    <form method="post" action="<?= e(url('/criar-conta')) ?>" class="form">
        <?= csrf_field() ?>
        <label>Nome
            <input type="text" name="name" value="<?= e(old('name')) ?>" required minlength="3">
            <?php if ($msg = error_field('name')): ?><span class="field-err"><?= e($msg) ?></span><?php endif; ?>
        </label>
        <label>E-mail
            <input type="email" name="email" value="<?= e(old('email')) ?>" required>
            <?php if ($msg = error_field('email')): ?><span class="field-err"><?= e($msg) ?></span><?php endif; ?>
        </label>
        <label>Senha
            <input type="password" name="password" required minlength="8" autocomplete="new-password">
            <?php if ($msg = error_field('password')): ?><span class="field-err"><?= e($msg) ?></span><?php endif; ?>
        </label>
        <label>Confirmar senha
            <input type="password" name="password_confirmation" required minlength="8">
        </label>
        <fieldset class="intent">
            <legend>Como você quer começar?</legend>
            <?php $intent = old('account_intent', ($intent ?? 'buyer') === 'seller' ? 'seller' : 'buyer'); ?>
            <label class="choice"><input type="radio" name="account_intent" value="buyer" <?= $intent === 'buyer' ? 'checked' : '' ?>> Contratar profissionais</label>
            <label class="choice"><input type="radio" name="account_intent" value="seller" <?= $intent === 'seller' ? 'checked' : '' ?>> Vender serviços</label>
        </fieldset>
        <button class="btn btn-accent btn-block" type="submit">Criar conta</button>
    </form>
    <p>Já tem conta? <a href="<?= e(url('/entrar')) ?>">Entrar</a></p>
</section>
