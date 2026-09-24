<?php
$s = $service;
$base = '/servico/' . $s['category_slug'] . '/' . $s['slug'];
$activePackage = $packages[0] ?? null;
$location = trim((string) ($s['state'] ?? '')) !== '' ? (string) $s['state'] : 'Brasil';
$memberYears = $s['member_since'] ? max(0, (int) floor((time() - strtotime((string) $s['member_since'])) / 31536000)) : 0;
$response = (int) ($s['avg_response_minutes'] ?? 0);
$responseLabel = $response <= 0 ? '—' : ($response < 60 ? $response . ' min' : round($response / 60, 1) . ' h');
$levelMap = ['basic' => 'básico', 'intermediate' => 'intermediário', 'fluent' => 'fluente', 'native' => 'nativo'];
$sellerName = seller_short_name((string) $s['display_name']);
$activeHours = $activePackage ? package_hours($activePackage) : 2;
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
            <p class="lead"><?= e(redact_public_contact((string) $s['short_description'])) ?></p>
            <div class="product-meta">
                <span><?= number_format((float) $s['rating_avg'], 1, ',', '.') ?> · <?= (int) $s['rating_count'] ?> avaliações</span>
                <span><?= (int) $s['orders_count'] ?> contratos</span>
                <span>a partir de <?= $activeHours ?>h · <?= money($s['starting_price_cents']) ?></span>
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
            <section class="gallery" aria-label="Takes em câmera">
                <?php if ($gallery): ?>
                    <figure class="gallery-main">
                        <img id="gallery-main" src="<?= e(media($gallery[0]['path'])) ?>" alt="<?= e($gallery[0]['alt_text'] ?: $s['title']) ?>" width="960" height="620">
                        <span class="badge-cam">Rosto em câmera</span>
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
                <h2>O que a empresa compra</h2>
                <div class="prose">
                    <?= safe_html(redact_public_contact((string) $s['description'])) ?>
                </div>
            </section>

            <?php if ($packages): ?>
            <section class="panel">
                <h2>Horas de vídeo</h2>
                <div class="table-wrap">
                    <table class="compare">
                        <thead>
                            <tr>
                                <th>Incluso</th>
                                <?php foreach ($packages as $pkg): ?>
                                    <th><?= e($tierLabels[$pkg['tier']] ?? $pkg['name']) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th>Horas</th>
                                <?php foreach ($packages as $pkg): ?><td><?= package_hours($pkg) ?>h</td><?php endforeach; ?>
                            </tr>
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
                            <?php
                            $maxBenefits = 0;
                            foreach ($packages as $pkg) {
                                $maxBenefits = max($maxBenefits, count($pkg['benefits']));
                            }
                            for ($i = 0; $i < $maxBenefits; $i++):
                            ?>
                                <tr>
                                    <th><?= $i === 0 ? 'O que entra' : '' ?></th>
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
                <h2>Perguntas frequentes</h2>
                <div class="faq">
                    <?php foreach ($faqs as $faq): ?>
                        <details>
                            <summary><?= e($faq['question']) ?></summary>
                            <p><?= e(redact_public_contact((string) $faq['answer'])) ?></p>
                        </details>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <section class="panel" id="profissional">
                <h2>Sobre o criador</h2>
                <div class="seller-hero">
                    <?php if (!empty($s['avatar_path'])): ?>
                        <img class="avatar-img avatar-img--lg" src="<?= e(media($s['avatar_path'])) ?>" alt="<?= e($sellerName) ?>" width="88" height="88">
                    <?php endif; ?>
                    <div>
                        <p class="seller-name">
                            <?= e($sellerName) ?>
                            <?php if (!empty($s['is_verified'])): ?><span class="badge">Verificado</span><?php endif; ?>
                            <?php if (is_online($s['last_seen_at'] ?? null)): ?><span class="badge badge-live">Online agora</span><?php endif; ?>
                        </p>
                        <p><?= e(redact_public_contact((string) ($s['headline'] ?? ''))) ?></p>
                    </div>
                </div>
                <?php if (!empty($s['bio'])): ?>
                    <p class="bio"><?= e(redact_public_contact((string) $s['bio'])) ?></p>
                <?php endif; ?>
                <p class="privacy-note">Telefone, e-mail e WhatsApp não ficam neste anúncio. A empresa conversa e envia o tema só pela plataforma.</p>
                <dl class="facts">
                    <div><dt>Atua em</dt><dd><?= e($location) ?></dd></div>
                    <div><dt>Na plataforma</dt><dd><?= $memberYears > 0 ? $memberYears . ' anos' : 'Neste ano' ?></dd></div>
                    <div><dt>Taxa de resposta</dt><dd><?= number_format((float) $s['response_rate'], 0) ?>%</dd></div>
                    <div><dt>Tempo médio</dt><dd><?= e($responseLabel) ?></dd></div>
                    <div><dt>Horas já entregues</dt><dd><?= (int) $s['orders_completed'] ?> contratos</dd></div>
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
                    <p class="chip-label">Especialidades</p>
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
                <h2>Outros pacotes deste criador</h2>
                <div class="card-grid card-grid--3">
                    <?php foreach ($others as $item): ?>
                        <?= \App\Core\View::component('service-card', ['service' => $item]) ?>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <section class="panel">
                <h2>Avaliações de empresas</h2>
                <?php if (!empty($reviews)): ?>
                    <p class="muted">Nota média <?= number_format((float) $s['rating_avg'], 1, ',', '.') ?> · <?= (int) $s['rating_count'] ?> registros. Os comentários abaixo são de pedidos fictícios de demonstração.</p>
                    <div class="review-list">
                        <?php foreach ($reviews as $review): ?>
                            <article class="review-card">
                                <header>
                                    <strong><?= e($review['author_name']) ?></strong>
                                    <?php if (!empty($review['company_name'])): ?>
                                        <span><?= e($review['company_name']) ?></span>
                                    <?php endif; ?>
                                    <em><?= str_repeat('★', max(1, min(5, (int) $review['rating']))) ?></em>
                                </header>
                                <p><?= e($review['body']) ?></p>
                                <time datetime="<?= e((string) $review['created_at']) ?>"><?= date('d/m/Y', strtotime((string) $review['created_at'])) ?></time>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="muted">Ainda não há avaliações públicas neste anúncio. A nota média atual é <?= number_format((float) $s['rating_avg'], 1, ',', '.') ?>.</p>
                <?php endif; ?>
            </section>
        </div>

        <aside class="product-aside">
            <div class="buy-box" data-buybox>
                <div class="pkg-tabs" role="tablist">
                    <?php foreach ($packages as $i => $pkg): ?>
                        <?php $hours = package_hours($pkg); ?>
                        <button type="button" role="tab" class="pkg-tab <?= $i === 0 ? 'is-active' : '' ?>"
                            data-package
                            data-id="<?= (int) $pkg['id'] ?>"
                            data-price="<?= (int) $pkg['price_cents'] ?>"
                            data-days="<?= (int) $pkg['delivery_days'] ?>"
                            data-hours="<?= $hours ?>"
                            data-revisions="<?= (int) $pkg['revisions'] ?>">
                            <?= $hours ?>h
                        </button>
                    <?php endforeach; ?>
                    <?php if (!$packages): ?>
                        <span class="pkg-tab is-active">2h</span>
                    <?php endif; ?>
                </div>

                <div class="buy-price">
                    <strong data-total><?= money($activePackage['price_cents'] ?? $s['starting_price_cents']) ?></strong>
                    <span class="muted" data-days-label><?= $activeHours ?>h de vídeo · <?= (int) ($activePackage['delivery_days'] ?? $s['min_delivery_days']) ?> dias</span>
                </div>

                <?php foreach ($packages as $i => $pkg): ?>
                    <div class="pkg-body" data-package-body <?= $i === 0 ? '' : 'hidden' ?>>
                        <h3><?= package_hours($pkg) ?> horas de vídeo</h3>
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
                                <input type="checkbox" name="extras[]" form="hire-form" data-extra data-price="<?= (int) $extra['price_cents'] ?>" data-days="<?= (int) $extra['extra_days'] ?>" data-id="<?= (int) $extra['id'] ?>" value="<?= (int) $extra['id'] ?>">
                                <span>
                                    <strong><?= e($extra['name']) ?></strong>
                                    <small><?= e($extra['description'] ?? '') ?> · +<?= money($extra['price_cents']) ?><?= (int) $extra['extra_days'] > 0 ? ' · +' . (int) $extra['extra_days'] . ' dias' : '' ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form id="hire-form" method="post" action="<?= e(url($base . '/contratar')) ?>" class="buy-actions">
                    <?= csrf_field() ?>
                    <input type="hidden" name="package_id" data-package-input value="<?= (int) ($activePackage['id'] ?? 0) ?>">
                    <label class="theme-field">Empresa
                        <input type="text" name="company_name" maxlength="120" placeholder="Nome da empresa (opcional)">
                    </label>
                    <label class="theme-field">Tema do vídeo
                        <textarea name="theme" required minlength="8" rows="4" placeholder="Quem paga define o tema: produto, treino interno, depoimento, lançamento…"></textarea>
                    </label>
                    <button class="btn btn-accent btn-block" type="submit">Contratar horas</button>
                </form>
                <form method="post" action="<?= e(url($base . '/falar')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-ink btn-block" type="submit">Mensagem na plataforma</button>
                </form>
                <p class="privacy-note">Sem telefone público. O tema e o combinado ficam só entre empresa e criador aqui dentro.</p>
            </div>

            <a class="seller-mini" href="#profissional">
                <?php if (!empty($s['avatar_path'])): ?>
                    <img src="<?= e(media($s['avatar_path'])) ?>" alt="" width="44" height="44">
                <?php endif; ?>
                <span>
                    <strong><?= e($sellerName) ?></strong>
                    <small><?= e($location) ?><?= !empty($s['is_verified']) ? ' · verificado' : '' ?></small>
                </span>
            </a>
        </aside>
    </div>
</article>
