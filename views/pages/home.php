<?php
$hero = $banners[0] ?? null;
$brand = brand_name();
?>
<section class="hero hero--company">
    <div class="hero-copy">
        <p class="eyebrow">Para empresas</p>
        <h1><?= e($hero['title'] ?? 'Encontre quem grava o vídeo da sua empresa') ?></h1>
        <p class="lead"><?= e($hero['subtitle'] ?? 'Na ' . $brand . ', a empresa contrata 2, 4, 6 ou 8 horas de vídeo. O tema é definido por quem paga. Telefone e WhatsApp do criador não aparecem no anúncio.') ?></p>
        <div class="hero-actions">
            <a class="btn btn-accent" href="<?= e(url($hero['cta_url'] ?? '/buscar')) ?>"><?= e($hero['cta_label'] ?? 'Ver criadores') ?></a>
            <a class="btn btn-ghost" href="<?= e(url('/criar-conta?intent=seller')) ?>">Anunciar horas</a>
        </div>
        <ul class="trust-pills">
            <li>Pacotes de 2, 4, 6 e 8 horas</li>
            <li>Tema definido pela empresa</li>
            <li>Contato só pela plataforma</li>
        </ul>
    </div>
    <div class="hero-visual">
        <?php if (!empty($hero['image_path'])): ?>
            <img src="<?= e(media($hero['image_path'])) ?>" alt="" width="960" height="540" fetchpriority="high">
        <?php endif; ?>
    </div>
</section>

<section class="section how-section">
    <div class="section-head">
        <h2>Como a empresa contrata</h2>
    </div>
    <div class="how-grid">
        <article class="how-card">
            <span>1</span>
            <h3>Escolha o criador</h3>
            <p>Veja o estilo, a nota e o preço da hora. O perfil público não mostra telefone, e-mail nem WhatsApp.</p>
        </article>
        <article class="how-card">
            <span>2</span>
            <h3>Compre as horas</h3>
            <p>2, 4, 6 ou 8 horas de vídeo. Quem paga define o tema, o roteiro e o recado da empresa.</p>
        </article>
        <article class="how-card">
            <span>3</span>
            <h3>Combine aqui dentro</h3>
            <p>Briefing, arquivos e entrega ficam na plataforma. Nada de contato pessoal exposto no anúncio.</p>
        </article>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <h2>Criadores em destaque</h2>
        <a href="<?= e(url('/buscar')) ?>">Ver todos</a>
    </div>
    <?php if ($featured): ?>
        <div class="card-grid card-grid--vitrine">
            <?php foreach ($featured as $service): ?>
                <?= \App\Core\View::component('service-card', ['service' => $service]) ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <?= \App\Core\View::component('empty', ['message' => 'Ainda não há anúncios em destaque.']) ?>
    <?php endif; ?>
</section>

<section class="section">
    <div class="section-head">
        <h2>Horas de vídeo mais contratadas</h2>
        <a href="<?= e(url('/buscar')) ?>">Ver todos</a>
    </div>
    <?php if ($bestSelling): ?>
        <div class="card-grid card-grid--vitrine">
            <?php foreach ($bestSelling as $service): ?>
                <?= \App\Core\View::component('service-card', ['service' => $service]) ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <?= \App\Core\View::component('empty', ['message' => 'Os mais contratados aparecem quando houver pedidos.']) ?>
    <?php endif; ?>
</section>

<section class="section">
    <div class="section-head">
        <h2>Novos anúncios de horas</h2>
        <a href="<?= e(url('/buscar')) ?>">Ver todos</a>
    </div>
    <?php if ($recentServices): ?>
        <div class="card-grid card-grid--vitrine">
            <?php foreach ($recentServices as $service): ?>
                <?= \App\Core\View::component('service-card', ['service' => $service]) ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <?= \App\Core\View::component('empty', ['message' => 'Nenhuma publicação recente.']) ?>
    <?php endif; ?>
</section>

<section class="section">
    <div class="section-head">
        <h2>Vídeo feito para a sua empresa</h2>
        <a href="<?= e(url('/buscar')) ?>">Explorar</a>
    </div>
    <?php if ($specialized): ?>
        <div class="card-grid card-grid--vitrine">
            <?php foreach ($specialized as $service): ?>
                <?= \App\Core\View::component('service-card', ['service' => $service]) ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <?= \App\Core\View::component('empty', ['message' => 'Em breve, mais especialidades.']) ?>
    <?php endif; ?>
</section>

<section class="section">
    <div class="section-head">
        <h2>Tipos de vídeo</h2>
        <a href="<?= e(url('/buscar')) ?>">Ver todos</a>
    </div>
    <div class="cat-grid">
        <?php foreach ($popularCategories as $cat): ?>
            <a class="cat-card" href="<?= e(url('/buscar?categoria=' . $cat['slug'])) ?>">
                <?php if (!empty($cat['image_path'])): ?>
                    <img src="<?= e(media($cat['image_path'])) ?>" alt="<?= e($cat['name']) ?>" width="480" height="320" loading="lazy">
                <?php endif; ?>
                <span class="cat-copy">
                    <span><?= e($cat['name']) ?></span>
                    <small><?= e($cat['short_description'] ?? '') ?></small>
                </span>
            </a>
        <?php endforeach; ?>
        <?php if (!$popularCategories): ?>
            <?= \App\Core\View::component('empty', ['message' => 'Categorias serão exibidas após o cadastro do catálogo.']) ?>
        <?php endif; ?>
    </div>
</section>
