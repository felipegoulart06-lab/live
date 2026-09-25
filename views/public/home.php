<?php
$heroImage = (string) setting('home_hero_image', 'images/hero-studio.jpg');
$sections = [
    ['Mais contratados', 'Criadores que as empresas mais voltaram a contratar.', $mostHired, '/anuncios?ordem=contratados'],
    ['Vídeo para empresas', 'Institucional, treinamento e comunicação interna.', $forCompanies, '/anuncios'],
    ['Novos anúncios', 'Acabaram de ser aprovados pela nossa equipe.', $newListings, '/anuncios?ordem=novos'],
];
?>
<section class="hero">
    <div>
        <p class="eyebrow">Horas de vídeo com criadores</p>
        <h1><?= e((string) setting('home_hero_title', 'Contrate horas de vídeo com um rosto para a sua marca')) ?></h1>
        <p class="lead"><?= e((string) setting('home_hero_subtitle', '')) ?></p>
        <div class="hero-actions">
            <a class="btn btn-accent" href="<?= e(url('/anuncios')) ?>">Encontrar criadores</a>
            <a class="btn btn-ghost" href="<?= e(url('/cadastro?tipo=criador')) ?>">Quero anunciar minhas horas</a>
        </div>
        <ul class="trust-pills">
            <li>Anúncios revisados pela equipe</li>
            <li>Pagamento dentro da plataforma</li>
            <li>Avaliações de empresas reais</li>
        </ul>
    </div>
    <?php if ($heroImage !== ''): ?>
        <div class="hero-visual"><img src="<?= e(media($heroImage)) ?>" alt="Criadora gravando em estúdio" width="640" height="420"></div>
    <?php endif; ?>
</section>

<section class="section" aria-labelledby="como">
    <div class="section-head"><h2 id="como">Como funciona</h2><a href="<?= e(url('/como-funciona')) ?>">Ver detalhes</a></div>
    <div class="how-grid">
        <div class="how-card"><span>1</span><h3>Escolha o criador</h3><p>Compare pacotes de horas, prazos, estilo e avaliações de outras empresas.</p></div>
        <div class="how-card"><span>2</span><h3>Envie a solicitação</h3><p>Conte o tema e o briefing. O criador aceita ou recusa e o contrato é criado.</p></div>
        <div class="how-card"><span>3</span><h3>Receba e avalie</h3><p>O pagamento fica retido até a conclusão. No final, você avalia o trabalho.</p></div>
    </div>
</section>

<?php if ($featuredCreators !== []): ?>
    <section class="section" aria-labelledby="destaques">
        <div class="section-head"><h2 id="destaques">Criadores em destaque</h2><a href="<?= e(url('/criadores')) ?>">Ver todos</a></div>
        <div class="card-grid">
            <?php foreach ($featuredCreators as $item): ?><?= view('creator-card', ['item' => $item]) ?><?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php foreach ($sections as $i => [$heading, $sub, $items, $link]): ?>
    <?php if ($items === []) { continue; } ?>
    <section class="section" aria-labelledby="sec-<?= $i ?>">
        <div class="section-head">
            <div><h2 id="sec-<?= $i ?>" class="mb-0"><?= e($heading) ?></h2><p class="muted small mb-0"><?= e($sub) ?></p></div>
            <a href="<?= e(url($link)) ?>">Ver mais</a>
        </div>
        <div class="card-grid card-grid--5">
            <?php foreach (array_slice($items, 0, 5) as $item): ?><?= view('listing-card', ['item' => $item]) ?><?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>

<?php if ($videoTypes !== []): ?>
    <section class="section" aria-labelledby="tipos">
        <div class="section-head"><h2 id="tipos">Tipos de vídeo</h2><a href="<?= e(url('/anuncios')) ?>">Todos os anúncios</a></div>
        <div class="card-grid">
            <?php foreach ($videoTypes as $cat): ?>
                <a class="cat-card" href="<?= e(category_url($cat['slug'])) ?>">
                    <?php if (!empty($cat['image_path'])): ?><img src="<?= e(media($cat['image_path'])) ?>" alt="" loading="lazy"><?php endif; ?>
                    <span class="cat-copy"><strong><?= e($cat['name']) ?></strong><small class="clamp"><?= e($cat['description'] ?? '') ?></small></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($faqs !== []): ?>
    <section class="section" aria-labelledby="faq">
        <div class="section-head"><h2 id="faq">Perguntas frequentes</h2><a href="<?= e(url('/ajuda')) ?>">Central de ajuda</a></div>
        <div class="panel faq">
            <?php foreach ($faqs as $faq): ?>
                <details><summary><?= e($faq['question']) ?></summary><p><?= e($faq['answer']) ?></p></details>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
