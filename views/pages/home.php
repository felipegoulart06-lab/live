<?php
$hero = $banners[0] ?? null;
?>
<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">Marketplace de ofícios</p>
        <h1><?= e($hero['title'] ?? 'Encontre o profissional certo para o próximo passo.') ?></h1>
        <p class="lead"><?= e($hero['subtitle'] ?? setting('tagline', '')) ?></p>
        <div class="hero-actions">
            <a class="btn btn-accent" href="<?= e(url($hero['cta_url'] ?? '/buscar')) ?>"><?= e($hero['cta_label'] ?? 'Explorar serviços') ?></a>
            <a class="btn btn-ghost" href="<?= e(url('/criar-conta?intent=seller')) ?>">Quero vender</a>
        </div>
    </div>
    <div class="hero-visual">
        <?php if (!empty($hero['image_path'])): ?>
            <img src="<?= e(media($hero['image_path'])) ?>" alt="" width="960" height="540" fetchpriority="high">
        <?php endif; ?>
        <div class="hero-panel">
            <div class="stat-grid">
                <div><span>Categorias</span><strong><?= count($menuCategories ?? []) ?></strong></div>
                <div><span>Serviços no ar</span><strong><?= count($recentServices ?? []) ?></strong></div>
                <div><span>Projetos abertos</span><strong><?= count($openProjects ?? []) ?></strong></div>
                <div><span>Profissionais</span><strong><?= count($newSellers ?? []) ?></strong></div>
            </div>
            <p class="muted">Números reais da base.</p>
        </div>
    </div>
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
            <?= \App\Core\View::component('empty', ['message' => 'Categorias serão exibidas após o seed.']) ?>
        <?php endif; ?>
    </div>
</section>

<?php
$blocks = [
    ['Serviços recomendados', $recommended],
    ['Serviços em destaque', $featured],
    ['Mais vendidos', $bestSelling],
    ['Publicados recentemente', $recentServices],
    ['Entrega rápida', $fastDelivery],
    ['Ofertas', $offers],
];
foreach ($blocks as [$label, $list]):
?>
<section class="section">
    <div class="section-head">
        <h2><?= e($label) ?></h2>
    </div>
    <?php if ($list): ?>
        <div class="card-grid">
            <?php foreach ($list as $service): ?>
                <?= \App\Core\View::component('service-card', ['service' => $service]) ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <?= \App\Core\View::component('empty', ['message' => 'Ainda não há serviços publicados nesta vitrine.']) ?>
    <?php endif; ?>
</section>
<?php endforeach; ?>

<section class="section">
    <div class="section-head"><h2>Novos profissionais</h2></div>
    <div class="people-grid">
        <?php foreach ($newSellers as $seller): ?>
            <article class="person-card">
                <?php if (!empty($seller['avatar_path'])): ?>
                    <img class="avatar-img" src="<?= e(media($seller['avatar_path'])) ?>" alt="<?= e($seller['display_name']) ?>" width="72" height="72" loading="lazy">
                <?php else: ?>
                    <div class="avatar"><?= e(mb_substr($seller['display_name'], 0, 1)) ?></div>
                <?php endif; ?>
                <h3><?= e($seller['display_name']) ?></h3>
                <p class="muted"><?= e($seller['headline'] ?? 'Profissional Nexo') ?></p>
            </article>
        <?php endforeach; ?>
        <?php if (!$newSellers): ?>
            <?= \App\Core\View::component('empty', ['message' => 'Os primeiros profissionais aparecerão aqui após o cadastro.']) ?>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="section-head"><h2>Melhor avaliados</h2></div>
    <div class="people-grid">
        <?php foreach ($topRated as $seller): ?>
            <article class="person-card">
                <?php if (!empty($seller['avatar_path'])): ?>
                    <img class="avatar-img" src="<?= e(media($seller['avatar_path'])) ?>" alt="<?= e($seller['display_name']) ?>" width="72" height="72" loading="lazy">
                <?php else: ?>
                    <div class="avatar"><?= e(mb_substr($seller['display_name'], 0, 1)) ?></div>
                <?php endif; ?>
                <h3><?= e($seller['display_name']) ?></h3>
                <p><?= number_format((float) $seller['rating_avg'], 1, ',', '.') ?> · <?= (int) $seller['orders_completed'] ?> pedidos</p>
            </article>
        <?php endforeach; ?>
        <?php if (!$topRated): ?>
            <?= \App\Core\View::component('empty', ['message' => 'Avaliações reais alimentam esta lista.']) ?>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="section-head"><h2>Profissionais verificados</h2></div>
    <div class="people-grid">
        <?php foreach ($verifiedSellers as $seller): ?>
            <article class="person-card">
                <?php if (!empty($seller['avatar_path'])): ?>
                    <img class="avatar-img" src="<?= e(media($seller['avatar_path'])) ?>" alt="<?= e($seller['display_name']) ?>" width="72" height="72" loading="lazy">
                <?php else: ?>
                    <div class="avatar"><?= e(mb_substr($seller['display_name'], 0, 1)) ?></div>
                <?php endif; ?>
                <h3><?= e($seller['display_name']) ?></h3>
                <p class="badge">Verificado</p>
            </article>
        <?php endforeach; ?>
        <?php if (!$verifiedSellers): ?>
            <?= \App\Core\View::component('empty', ['message' => 'Nenhum perfil verificado no momento.']) ?>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="section-head"><h2>Projetos procurando profissionais</h2></div>
    <?php if ($openProjects): ?>
        <div class="project-list">
            <?php foreach ($openProjects as $project): ?>
                <article class="project-row">
                    <div>
                        <p class="eyebrow"><?= e($project['category_name']) ?></p>
                        <h3><?= e($project['title']) ?></h3>
                    </div>
                    <p><?= money($project['budget_min_cents'] ?? 0) ?> — <?= money($project['budget_max_cents'] ?? 0) ?></p>
                    <p class="muted"><?= (int) $project['proposals_count'] ?> propostas</p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <?= \App\Core\View::component('empty', ['message' => 'Nenhum projeto aberto. Publique o primeiro.']) ?>
    <?php endif; ?>
</section>

<section class="section split">
    <div>
        <h2>Como funciona</h2>
        <ol class="steps">
            <li><strong>Descreva</strong> o que precisa, com prazo e orçamento.</li>
            <li><strong>Compare</strong> serviços prontos ou propostas sob medida.</li>
            <li><strong>Acompanhe</strong> briefing, entrega e revisões numa timeline.</li>
            <li><strong>Conclua</strong> com avaliação e registro financeiro real.</li>
        </ol>
    </div>
    <div class="cta-card cta-card--photo">
        <img src="<?= e(asset('images/svc-brand.jpg')) ?>" alt="" width="640" height="420" loading="lazy">
        <div>
            <h3>Para quem vende</h3>
            <p>Pacotes, adicionais, carteira, saque e analytics. Comissão configurável por plano e categoria.</p>
            <a class="btn btn-accent" href="<?= e(url('/criar-conta?intent=seller')) ?>">Abrir conta profissional</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-head"><h2>Depoimentos</h2></div>
    <div class="card-grid">
        <?php foreach ($testimonials as $item): ?>
            <blockquote class="quote-card">
                <?php if (!empty($item['avatar_path'])): ?>
                    <img class="avatar-img" src="<?= e(media($item['avatar_path'])) ?>" alt="<?= e($item['author_name']) ?>" width="56" height="56" loading="lazy">
                <?php endif; ?>
                <p><?= e($item['quote']) ?></p>
                <footer><?= e($item['author_name']) ?> · <?= e($item['author_role']) ?></footer>
            </blockquote>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <div class="section-head"><h2>Perguntas frequentes</h2></div>
    <div class="faq">
        <?php foreach ($faqs as $faq): ?>
            <details>
                <summary><?= e($faq['question']) ?></summary>
                <p><?= e($faq['answer']) ?></p>
            </details>
        <?php endforeach; ?>
    </div>
    <?php if ($faqs): ?>
        <script type="application/ld+json">
        <?= json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static fn ($f) => [
                '@type' => 'Question',
                'name' => $f['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']],
            ], $faqs),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        </script>
    <?php endif; ?>
</section>
