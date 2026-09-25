<section class="error-page">
    <p class="eyebrow">Manutenção</p>
    <h1>Voltamos em instantes</h1>
    <p class="lead">Estamos fazendo uma atualização na plataforma. Seus anúncios, contratos e mensagens continuam salvos.</p>
    <?php if (!$authUser): ?>
        <a class="btn btn-ghost" href="<?= e(url('/login')) ?>">Acesso da equipe</a>
    <?php endif; ?>
</section>
