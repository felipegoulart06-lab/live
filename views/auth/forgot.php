<h1>Recuperar senha</h1>
<p class="muted">Informe o e-mail da conta. Se ele estiver cadastrado, enviamos um link para criar uma nova senha.</p>
<form class="form" method="post" action="<?= e(url('/recuperar-senha')) ?>" novalidate>
    <?= csrf_field() ?>
    <?= view('field', ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'attrs' => 'required autocomplete="email" autofocus']) ?>
    <button class="btn btn-ink btn-block" type="submit">Enviar link</button>
</form>
<p class="auth-foot"><a href="<?= e(url('/login')) ?>">Voltar para o login</a></p>
