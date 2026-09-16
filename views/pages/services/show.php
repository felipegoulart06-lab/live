<?php
$s = $service;
$base = '/servico/' . $s['category_slug'] . '/' . $s['slug'];
$activePackage = $packages[0] ?? null;
$location = trim(implode(' · ', array_filter([$s['city'] ?? null, $s['state'] ?? null])));
$memberYears = $s['member_since'] ? max(0, (int) floor((time() - strtotime((string) $s['member_since'])) / 31536000)) : 0;
$response = (int) ($s['avg_response_minutes'] ?? 0);
$responseLabel = $response <= 0 ? '—' : ($response < 60 ? $response . ' min' : round($response / 60, 1) . ' h');
$levelMap = ['basic' => 'básico', 'intermediate' => 'intermediário', 'fluent' => 'fluente', 'native' => 'nativo'];
?>
<article class="product" data-product>
    <nav class="crumb" aria-label="Você está em">
        <a href="<?= e(url('/')) ?>">Início</a>
        <span>/</span>
        <a href="<?= e(url('/buscar?categoria=' . $s['category_slug'])) ?>"><?= e($s['category_name']) ?></a>
        <?php if (!empty($s['subcategory_name'])): ?>
            <span>/</span>
            <span><?= e($s['subcategory_name']) ?></span>
        <?php endif; ?>
        <span>/</span>
        <span aria-current="page"><?= e($s['title']) ?></span>
    </nav>

    <header class="product-head">
        <div>
            <p class="eyebrow"><?= e($s['category_name']) ?></p>
            <h1><?= e($s['title']) ?></h1>
            <p class="lead"><?= e($s['short_description']) ?></p>
            <div class="product-meta">
                <span><?= number_format((float) $s['rating_avg'], 1, ',', '.') ?> · <?= (int) $s['rating_count'] ?> avaliações</span>
                <span><?= (int) $s['orders_count'] ?> pedidos</span>
                <span><?= (int) $s['views_count'] ?> visualizações</span>
                <span>a partir de <?= money($s['starting_price_cents']) ?></span>
            </div>
        </div>
        <div class="product-toolbar">
            <form method="post" action="<?= e(url($base . '/favoritar')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-ghost" type="submit"><?= $favorited ? 'Salvo' : 'Favoritar' ?></button>
            </form>
            <button class="btn btn-ghost" type="button" data-share>Compartilhar</button>
        </div>
    </header>

    <div class="product-grid">
        <div class="product-main">
            <section class="gallery" aria-label="Galeria do serviço">
                <?php if ($gallery): ?>
                    <figure class="gallery-main">
                        <img id="gallery-main" src="<?= e(media($gallery[0]['path'])) ?>" alt="<?= e($gallery[0]['alt_text'] ?: $s['title']) ?>" width="960" height="620">
                    </figure>
                    <?php if (count($gallery) > 1): ?>
                        <div class="gallery-thumbs" role="list">
                            <?php foreach ($gallery as $i => $image): ?>
                                <button type="button" role="listitem" class="gallery-thumb <?= $i === 0 ? 'is-active' : '' ?>" data-gallery-src="<?= e(media($image['path'])) ?>" aria-label="Imagem <?= $i + 1 ?>">
                                    <img src="<?= e(media($image['path'])) ?>" alt="" width="120" height="80">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <section class="panel">
                <h2>Sobre o serviço</h2>
                <div class="prose">
                    <?= safe_html((string) $s['description']) ?>
                </div>
            </section>

            <?php if ($packages): ?>
            <section class="panel">
                <h2>Comparação de pacotes</h2>
                <div class="table-wrap">
                    <table class="compare">
                        <thead>
                            <tr>
                                <th>O que está incluso</th>
                                <?php foreach ($packages as $pkg): ?>
                                    <th><?= e($tierLabels[$pkg['tier']] ?? $pkg['name']) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th>Preço</th>
                                <?php foreach ($packages as $pkg): ?><td><?= money($pkg['price_cents']) ?></td><?php endforeach; ?>
                            </tr>
                            <tr>
                                <th>Prazo</th>
                                <?php foreach ($packages as $pkg): ?><td><?= (int) $pkg['delivery_days'] ?> dias</td><?php endforeach; ?>
                            </tr>
                            <tr>
                                <th>Revisões</th>
                                <?php foreach ($packages as $pkg): ?><td><?= (int) $pkg['revisions'] ?></td><?php endforeach; ?>
                            </tr>
                            <tr>
                                <th>Quantidade</th>
                                <?php foreach ($packages as $pkg): ?><td><?= (int) $pkg['quantity'] ?></td><?php endforeach; ?>
                            </tr>
                            <?php
                            $maxBenefits = 0;
                            foreach ($packages as $pkg) {
                                $maxBenefits = max($maxBenefits, count($pkg['benefits']));
                            }
                            for ($i = 0; $i < $maxBenefits; $i++):
                            ?>
                                <tr>
                                    <th><?= $i === 0 ? 'Benefícios' : '' ?></th>
                                    <?php foreach ($packages as $pkg): ?>
                                        <td><?= e($pkg['benefits'][$i] ?? '—') ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($faqs): ?>
            <section class="panel">
                <h2>Perguntas sobre este serviço</h2>
                <div class="faq">
                    <?php foreach ($faqs as $faq): ?>
                        <details>
                            <summary><?= e($faq['question']) ?></summary>
                            <p><?= e($faq['answer']) ?></p>
                        </details>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <section class="panel" id="profissional">
                <h2>Sobre o profissional</h2>
                <div class="seller-hero">
                    <?php if (!empty($s['avatar_path'])): ?>
                        <img class="avatar-img avatar-img--lg" src="<?= e(media($s['avatar_path'])) ?>" alt="<?= e($s['display_name']) ?>" width="88" height="88">
                    <?php endif; ?>
                    <div>
                        <p class="seller-name">
                            <?= e($s['display_name']) ?>
                            <?php if (!empty($s['is_verified'])): ?><span class="badge">Verificado</span><?php endif; ?>
                            <?php if (is_online($s['last_seen_at'] ?? null)): ?><span class="badge badge-live">Online agora</span><?php endif; ?>
                        </p>
                        <?php if (!empty($s['professional_name']) && $s['professional_name'] !== $s['display_name']): ?>
                            <p class="muted"><?= e($s['professional_name']) ?></p>
                        <?php endif; ?>
                        <p><?= e($s['headline'] ?? '') ?></p>
                    </div>
                </div>
                <?php if (!empty($s['bio'])): ?>
                    <p class="bio"><?= e($s['bio']) ?></p>
                <?php endif; ?>
                <dl class="facts">
                    <div><dt>Localização</dt><dd><?= e($location !== '' ? $location : 'Brasil') ?></dd></div>
                    <div><dt>Na plataforma</dt><dd><?= $memberYears > 0 ? $memberYears . ' anos' : 'Neste ano' ?></dd></div>
                    <div><dt>Último acesso</dt><dd><?= !empty($s['last_seen_at']) ? date('d/m/Y H:i', strtotime((string) $s['last_seen_at'])) : '—' ?></dd></div>
                    <div><dt>Taxa de resposta</dt><dd><?= number_format((float) $s['response_rate'], 0) ?>%</dd></div>
                    <div><dt>Tempo médio</dt><dd><?= e($responseLabel) ?></dd></div>
                    <div><dt>Pedidos concluídos</dt><dd><?= (int) $s['orders_completed'] ?></dd></div>
                    <div><dt>Avaliação</dt><dd><?= number_format((float) $s['seller_rating_avg'], 1, ',', '.') ?> (<?= (int) $s['seller_rating_count'] ?>)</dd></div>
                    <?php if (!empty($s['experience_years'])): ?>
                        <div><dt>Experiência</dt><dd><?= (int) $s['experience_years'] ?> anos</dd></div>
                    <?php endif; ?>
                </dl>
                <?php if ($languages): ?>
                    <p class="chip-label">Idiomas</p>
                    <div class="chips">
                        <?php foreach ($languages as $lang): ?>
                            <span class="chip"><?= e($lang['name']) ?> · <?= e($levelMap[$lang['level']] ?? $lang['level']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($skills): ?>
                    <p class="chip-label">Habilidades</p>
                    <div class="chips">
                        <?php foreach ($skills as $skill): ?>
                            <span class="chip"><?= e($skill) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($badges): ?>
                    <p class="chip-label">Selos</p>
                    <div class="chips">
                        <?php foreach ($badges as $badge): ?>
                            <span class="chip chip-ink"><?= e($badge['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php if ($others): ?>
            <section class="panel">
                <h2>Outros serviços deste profissional</h2>
                <div class="card-grid card-grid--3">
                    <?php foreach ($others as $item): ?>
                        <?= \App\Core\View::component('service-card', ['service' => $item]) ?>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <section class="panel">
                <h2>Avaliações</h2>
                <p class="muted">As avaliações públicas aparecem após pedidos concluídos. Nota média atual: <?= number_format((float) $s['rating_avg'], 1, ',', '.') ?> com <?= (int) $s['rating_count'] ?> registros.</p>
            </section>
        </div>

        <aside class="product-aside">
            <div class="buy-box" data-buybox>
                <div class="pkg-tabs" role="tablist">
                    <?php foreach ($packages as $i => $pkg): ?>
                        <button type="button" role="tab" class="pkg-tab <?= $i === 0 ? 'is-active' : '' ?>"
                            data-package
                            data-id="<?= (int) $pkg['id'] ?>"
                            data-price="<?= (int) $pkg['price_cents'] ?>"
                            data-days="<?= (int) $pkg['delivery_days'] ?>"
                            data-revisions="<?= (int) $pkg['revisions'] ?>">
                            <?= e($tierLabels[$pkg['tier']] ?? $pkg['name']) ?>
                        </button>
                    <?php endforeach; ?>
                    <?php if (!$packages): ?>
                        <span class="pkg-tab is-active">Pacote único</span>
                    <?php endif; ?>
                </div>

                <div class="buy-price">
                    <strong data-total><?= money($activePackage['price_cents'] ?? $s['starting_price_cents']) ?></strong>
                    <span class="muted" data-days-label><?= (int) ($activePackage['delivery_days'] ?? $s['min_delivery_days']) ?> dias · <?= (int) ($activePackage['revisions'] ?? 0) ?> revisões</span>
                </div>

                <?php foreach ($packages as $i => $pkg): ?>
                    <div class="pkg-body" data-package-body <?= $i === 0 ? '' : 'hidden' ?>>
                        <h3><?= e($pkg['name']) ?></h3>
                        <p><?= e($pkg['description']) ?></p>
                        <?php if ($pkg['benefits']): ?>
                            <ul class="check-list">
                                <?php foreach ($pkg['benefits'] as $benefit): ?>
                                    <li><?= e($benefit) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if ($extras): ?>
                    <div class="extras">
                        <p class="chip-label">Adicionais</p>
                        <?php foreach ($extras as $extra): ?>
                            <label class="extra">
                                <input type="checkbox" data-extra data-price="<?= (int) $extra['price_cents'] ?>" data-days="<?= (int) $extra['extra_days'] ?>" data-id="<?= (int) $extra['id'] ?>">
                                <span>
                                    <strong><?= e($extra['name']) ?></strong>
                                    <small><?= e($extra['description'] ?? '') ?> · +<?= money($extra['price_cents']) ?><?= (int) $extra['extra_days'] > 0 ? ' · +' . (int) $extra['extra_days'] . ' dias' : '' ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(url($base . '/contratar')) ?>" class="buy-actions">
                    <?= csrf_field() ?>
                    <input type="hidden" name="package_id" data-package-input value="<?= (int) ($activePackage['id'] ?? 0) ?>">
                    <button class="btn btn-accent btn-block" type="submit">Contratar agora</button>
                </form>
                <form method="post" action="<?= e(url($base . '/falar')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-ink btn-block" type="submit">Falar com o profissional</button>
                </form>
                <form method="post" action="<?= e(url($base . '/orcamento')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-ghost btn-block" type="submit">Solicitar orçamento</button>
                </form>
            </div>

            <a class="seller-mini" href="#profissional">
                <?php if (!empty($s['avatar_path'])): ?>
                    <img src="<?= e(media($s['avatar_path'])) ?>" alt="" width="44" height="44">
                <?php endif; ?>
                <span>
                    <strong><?= e($s['display_name']) ?></strong>
                    <small><?= e($location !== '' ? $location : 'Brasil') ?><?= !empty($s['is_verified']) ? ' · verificado' : '' ?></small>
                </span>
            </a>
        </aside>
    </div>
</article>
