<h1>Nova senha</h1>
<?php if (!$valid): ?>
    <div class="alert alert-err">Este link expirou ou já foi usado.</div>
    <a class="btn btn-ink btn-block" href="<?= e(url('/recuperar-senha')) ?>">Pedir um novo link</a>
<?php else: ?>
    <p class="muted">Escolha uma senha nova. Por segurança, as outras sessões abertas serão encerradas.</p>
    <form class="form" method="post" action="<?= e(url('/recuperar-senha/' . rawurlencode($token))) ?>" novalidate>
        <?= csrf_field() ?>
        <?= view('field', ['name' => 'password', 'label' => 'Nova senha', 'type' => 'password', 'hint' => 'Mínimo de 8 caracteres, com letras e números.', 'attrs' => 'required minlength="8" autocomplete="new-password"']) ?>
        <?= view('field', ['name' => 'password_confirmation', 'label' => 'Confirmar senha', 'type' => 'password', 'attrs' => 'required autocomplete="new-password"']) ?>
        <button class="btn btn-ink btn-block" type="submit">Salvar nova senha</button>
    </form>
<?php endif; ?>
