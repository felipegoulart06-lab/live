<?php
$defaults = [
    403 => 'Você não tem permissão para acessar esta página.',
    404 => 'O endereço pode ter mudado ou o conteúdo não está mais disponível.',
    429 => 'Muitas tentativas em pouco tempo. Aguarde alguns minutos e tente de novo.',
    500 => 'Tivemos um problema ao carregar esta página. Tente novamente em instantes.',
];
$text = trim((string) ($message ?? '')) !== '' ? $message : ($defaults[$status] ?? $defaults[500]);
?>
<section class="error-page">
    <p class="error-code" aria-hidden="true"><?= (int) $status ?></p>
    <h1><?= e($title) ?></h1>
    <p class="lead"><?= e($text) ?></p>
    <div class="row" style="justify-content:center">
        <?php if ($status === 403 && !$authUser): ?>
            <a class="btn btn-ink" href="<?= e(url('/login')) ?>">Entrar</a>
        <?php elseif ($authUser): ?>
            <a class="btn btn-ink" href="<?= e(url($authUser->homePath())) ?>">Ir para o meu painel</a>
        <?php endif; ?>
        <a class="btn btn-ghost" href="<?= e(url('/anuncios')) ?>">Ver anúncios</a>
    </div>
</section>
