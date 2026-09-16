<?php
$hero = $banners[0] ?? null;
?>
<section class="hero hero--seller">
    <div class="hero-copy">
        <p class="eyebrow">Para quem vende</p>
        <h1><?= e($hero['title'] ?? 'Transforme seu talento em renda extra hoje!') ?></h1>
        <p class="lead"><?= e($hero['subtitle'] ?? 'Na ' . brand_name() . ', novas oportunidades de trabalho estão à sua espera. Explore projetos da comunidade e transforme suas habilidades em lucro.') ?></p>
        <div class="hero-actions">
            <a class="btn btn-accent" href="<?= e(url($hero['cta_url'] ?? '/buscar')) ?>"><?= e($hero['cta_label'] ?? 'Começar agora') ?></a>
            <a class="btn btn-ghost" href="<?= e(url('/criar-conta?intent=seller')) ?>">Quero vender</a>
        </div>
    </div>
    <div class="hero-visual">
        <?php if (!empty($hero['image_path'])): ?>
            <img src="<?= e(media($hero['image_path'])) ?>" alt="" width="960" height="540" fetchpriority="high">
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <h2>Em destaque</h2>
        <a href="<?= e(url('/buscar')) ?>">Ver todos</a>
    </div>
    <?php if ($featured): ?>
        <div class="card-grid card-grid--vitrine">
            <?php foreach ($featured as $service): ?>
                <?= \App\Core\View::component('service-card', ['service' => $service]) ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <?= \App\Core\View::component('empty', ['message' => 'Ainda não há serviços em destaque.']) ?>
    <?php endif; ?>
</section>

<section class="section">
    <div class="section-head">
        <h2>Serviços de freelancers mais vendidos</h2>
        <a href="<?= e(url('/buscar')) ?>">Ver todos</a>
    </div>
    <?php if ($bestSelling): ?>
        <div class="card-grid card-grid--vitrine">
            <?php foreach ($bestSelling as $service): ?>
                <?= \App\Core\View::component('service-card', ['service' => $service]) ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <?= \App\Core\View::component('empty', ['message' => 'Os mais vendidos aparecem quando houver pedidos.']) ?>
    <?php endif; ?>
</section>

<section class="section">
    <div class="section-head">
        <h2>Serviços de freelancers publicados hoje</h2>
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
        <h2>Contrate serviços de freelancers especializados para o seu negócio</h2>
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
        <h2>Categorias populares</h2>
        <a href="<?= e(url('/buscar')) ?>">Ver todas</a>
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
