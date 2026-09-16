<section class="auth-card">
    <h1>Recuperar senha</h1>
    <p class="muted">Enviaremos um link se o e-mail existir na base.</p>
    <form method="post" action="<?= e(url('/esqueci-senha')) ?>" class="form">
        <?= csrf_field() ?>
        <label>E-mail
            <input type="email" name="email" required>
        </label>
        <button class="btn btn-accent btn-block" type="submit">Enviar instruções</button>
    </form>
</section>
