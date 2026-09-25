<?php $selected = (string) old('role', $role); ?>
<h1>Criar conta</h1>
<p class="muted">Escolha como você vai usar a plataforma. O tipo de conta não pode ser trocado depois.</p>
<form class="form" method="post" action="<?= e(url('/cadastro')) ?>" novalidate>
    <?= csrf_field() ?>
    <fieldset>
        <legend>Tipo de conta</legend>
        <div class="choice-cards">
            <label class="choice-card">
                <input type="radio" name="role" value="empresa" <?= $selected === 'empresa' ? 'checked' : '' ?> data-toggle-company="1">
                <strong>Sou empresa</strong>
                <span>Quero contratar horas de vídeo.</span>
            </label>
            <label class="choice-card">
                <input type="radio" name="role" value="criador" <?= $selected === 'criador' ? 'checked' : '' ?> data-toggle-company="0">
                <strong>Sou criador</strong>
                <span>Quero vender minhas horas.</span>
            </label>
        </div>
        <?php if ($m = error_field('role')): ?><span class="field-err"><?= e($m) ?></span><?php endif; ?>
    </fieldset>
    <div data-company-only <?= $selected === 'criador' ? 'hidden' : '' ?>>
        <?= view('field', ['name' => 'company_name', 'label' => 'Nome da empresa', 'attrs' => 'maxlength="120" autocomplete="organization"']) ?>
    </div>
    <?= view('field', ['name' => 'display_name', 'label' => 'Seu nome', 'attrs' => 'required maxlength="80" autocomplete="name"']) ?>
    <?= view('field', ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'attrs' => 'required autocomplete="email"']) ?>
    <?= view('field', ['name' => 'password', 'label' => 'Senha', 'type' => 'password', 'hint' => 'Mínimo de 8 caracteres, com letras e números.', 'attrs' => 'required minlength="8" autocomplete="new-password"']) ?>
    <?= view('field', ['name' => 'password_confirmation', 'label' => 'Confirmar senha', 'type' => 'password', 'attrs' => 'required autocomplete="new-password"']) ?>
    <label class="check">
        <input type="checkbox" name="terms" value="1" required <?= old('terms') ? 'checked' : '' ?>>
        <span>Li e aceito os <a href="<?= e(url('/termos')) ?>" target="_blank" rel="noopener">termos de uso</a> e a <a href="<?= e(url('/privacidade')) ?>" target="_blank" rel="noopener">política de privacidade</a>.</span>
    </label>
    <?php if ($m = error_field('terms')): ?><span class="field-err"><?= e($m) ?></span><?php endif; ?>
    <button class="btn btn-accent btn-block" type="submit">Criar conta</button>
</form>
<p class="auth-foot">Já tem conta? <a href="<?= e(url('/login')) ?>">Entrar</a></p>
