<h1>Entrar</h1>
<p class="muted">Acesse o painel de criador, de empresa ou da equipe.</p>
<form class="form" method="post" action="<?= e(url('/login')) ?>" novalidate>
    <?= csrf_field() ?>
    <?= view('field', ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'attrs' => 'required autocomplete="email" autofocus']) ?>
    <?= view('field', ['name' => 'password', 'label' => 'Senha', 'type' => 'password', 'attrs' => 'required autocomplete="current-password"']) ?>
    <div class="row-between">
        <a class="small" href="<?= e(url('/recuperar-senha')) ?>">Esqueci minha senha</a>
    </div>
    <button class="btn btn-ink btn-block" type="submit">Entrar</button>
</form>
<p class="auth-foot">Ainda não tem conta? <a href="<?= e(url('/cadastro')) ?>">Criar conta</a></p>
