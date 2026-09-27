<?php
use App\Controllers\Admin\ModerationController;

/** @var array<string, mixed> $listing */
$isCompany = $authUser && $authUser->isCompany();
$canAct = !$preview;
$cover = $images[0] ?? null;
$firstPackage = $packages[0] ?? null;
$oldPackage = (string) old('package_id', $firstPackage['id'] ?? '');
$oldAddons = array_map('strval', (array) old('addons', []));
$unavailable = !empty($listing['unavailable_until']) && $listing['unavailable_until'] >= date('Y-m-d');
?>
<nav class="crumb" aria-label="Você está em">
    <a href="<?= e(url('/')) ?>">Início</a><span aria-hidden="true">/</span>
    <a href="<?= e(url('/anuncios')) ?>">Anúncios</a>
    <?php if (!empty($listing['category_slug'])): ?>
        <span aria-hidden="true">/</span><a href="<?= e(category_url($listing['category_slug'])) ?>"><?= e($listing['category_name']) ?></a>
    <?php endif; ?>
</nav>

<header class="product-head">
    <div>
        <h1><?= e($listing['title']) ?></h1>
        <p class="lead mb-0"><?= e(redact_contact((string) $listing['short_description'])) ?></p>
        <div class="product-meta">
            <?php if ((int) $listing['rating_count'] > 0): ?>
                <span><span class="stars" aria-hidden="true">★</span> <?= number_format((float) $listing['rating_avg'], 1, ',', '') ?> · <?= (int) $listing['rating_count'] ?> avaliações</span>
            <?php else: ?>
                <span>Ainda sem avaliações</span>
            <?php endif; ?>
            <span><?= (int) $listing['contracts_count'] ?> contratos concluídos</span>
        </div>
    </div>
    <?php if ($canAct): ?>
        <div class="row">
            <?php if ($authUser): ?>
                <form method="post" action="<?= e(url('/favoritos')) ?>" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="type" value="listing">
                    <input type="hidden" name="slug" value="<?= e($listing['slug']) ?>">
                    <button class="btn btn-ghost" type="submit" aria-pressed="<?= $favorited ? 'true' : 'false' ?>"><?= $favorited ? 'Salvo nos favoritos' : 'Salvar' ?></button>
                </form>
            <?php endif; ?>
            <button class="btn btn-ghost" type="button" data-share>Copiar link</button>
        </div>
    <?php endif; ?>
</header>

<div class="product-grid">
    <div class="stack">
        <?php if ($images !== []): ?>
            <div>
                <figure class="gallery-main"><img id="gallery-main" src="<?= e(media($cover['path'])) ?>" alt="<?= e($cover['alt_text'] ?: $listing['title']) ?>"></figure>
                <?php if (count($images) > 1): ?>
                    <div class="gallery-thumbs">
                        <?php foreach ($images as $i => $img): ?>
                            <button type="button" class="gallery-thumb<?= $i === 0 ? ' is-active' : '' ?>" data-gallery-src="<?= e(media($img['path'])) ?>" aria-label="Ver imagem <?= $i + 1 ?>">
                                <img src="<?= e(media($img['thumb_path'] ?: $img['path'])) ?>" alt="" loading="lazy">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($embed): ?>
            <section class="panel" aria-labelledby="video">
                <h2 id="video" class="panel-title">Vídeo de apresentação</h2>
                <div class="video-embed"><iframe src="<?= e($embed) ?>" title="Vídeo de apresentação" loading="lazy" allow="encrypted-media; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>
            </section>
        <?php endif; ?>

        <section class="panel" aria-labelledby="sobre">
            <h2 id="sobre" class="panel-title">Sobre o serviço</h2>
            <div class="prose"><?= nl2p($listing['description']) ?></div>
            <div class="info-list">
                <?php foreach ([
                    'what_company_buys' => 'O que a empresa contrata',
                    'what_company_provides' => 'O que a empresa precisa enviar',
                    'how_it_works' => 'Como funciona a gravação',
                    'framing' => 'Enquadramento e estilo',
                    'additional_info' => 'Informações adicionais',
                ] as $field => $label): ?>
                    <?php if (!empty($listing[$field])): ?>
                        <div><h3><?= e($label) ?></h3><p><?= e(redact_contact((string) $listing[$field])) ?></p></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if ($packages !== []): ?>
            <section class="panel" aria-labelledby="pacotes">
                <h2 id="pacotes" class="panel-title">Pacotes de horas</h2>
                <div class="table-wrap" style="border:0">
                    <table class="compare">
                        <thead><tr><th scope="col">Horas</th><th scope="col">Preço</th><th scope="col">Entrega</th><th scope="col">Revisões</th><th scope="col">Inclui</th></tr></thead>
                        <tbody>
                        <?php foreach ($packages as $pkg): ?>
                            <tr>
                                <th scope="row"><?= (int) $pkg['hours'] ?>h</th>
                                <td class="nowrap"><?= e(money($pkg['price_cents'])) ?></td>
                                <td class="nowrap"><?= (int) $pkg['delivery_days'] ?> dias</td>
                                <td><?= (int) $pkg['revisions'] ?></td>
                                <td class="muted"><?= e($pkg['description'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($faqs !== []): ?>
            <section class="panel faq" aria-labelledby="duvidas">
                <h2 id="duvidas" class="panel-title">Perguntas frequentes</h2>
                <?php foreach ($faqs as $faq): ?>
                    <details><summary><?= e(redact_contact((string) $faq['question'])) ?></summary><p><?= e(redact_contact((string) $faq['answer'])) ?></p></details>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section class="panel" aria-labelledby="avaliacoes">
            <h2 id="avaliacoes" class="panel-title">Avaliações de empresas</h2>
            <?php if ($reviews === []): ?>
                <p class="muted mb-0">Este anúncio ainda não recebeu avaliações. As avaliações aparecem depois que um contrato é concluído.</p>
            <?php else: ?>
                <div class="review-list">
                    <?php foreach ($reviews as $review): ?>
                        <article class="review-card">
                            <header>
                                <strong><?= e(public_company_label()) ?></strong>
                                <span class="stars" aria-label="<?= (int) $review['rating'] ?> de 5"><?= rating_stars((float) $review['rating']) ?></span>
                                <time datetime="<?= e($review['created_at']) ?>"><?= e(fmt_date($review['created_at'])) ?></time>
                            </header>
                            <?php if (!empty($review['comment'])): ?><p class="mb-0"><?= e(redact_contact((string) $review['comment'])) ?></p><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($others !== []): ?>
            <section aria-labelledby="outros">
                <h2 id="outros" class="panel-title">Outros anúncios deste serviço</h2>
                <div class="card-grid card-grid--3">
                    <?php foreach ($others as $item): ?><?= view('listing-card', ['item' => $item]) ?><?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <aside class="stack product-aside">
        <form class="buy-box" id="contratar" method="post" action="<?= e(url('/anuncios/' . $listing['slug'] . '/solicitar')) ?>" data-buybox>
            <?= csrf_field() ?>
            <?php if ($packages === []): ?>
                <p class="muted mb-0">Este anúncio ainda não tem pacotes.</p>
            <?php else: ?>
                <fieldset>
                    <legend>Quantas horas de vídeo?</legend>
                    <div class="pkg-options">
                        <?php foreach ($packages as $pkg): ?>
                            <label class="pkg-option">
                                <input type="radio" name="package_id" value="<?= (int) $pkg['id'] ?>" data-price="<?= (int) $pkg['price_cents'] ?>" data-days="<?= (int) $pkg['delivery_days'] ?>" data-hours="<?= (int) $pkg['hours'] ?>" <?= $oldPackage === (string) $pkg['id'] ? 'checked' : '' ?>>
                                <span><?= (int) $pkg['hours'] ?>h</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($m = error_field('package_id')): ?><p class="field-err"><?= e($m) ?></p><?php endif; ?>
                </fieldset>
                <?php if ($addons !== []): ?>
                    <fieldset class="upsell">
                        <legend>Adicione à sua compra</legend>
                        <p class="muted small mb-0">Marque extras para o vídeo render mais — o total atualiza na hora.</p>
                        <?php foreach ($addons as $addon): ?>
                            <label class="upsell-row<?= in_array((string) $addon['id'], $oldAddons, true) ? ' is-on' : '' ?>">
                                <input type="checkbox" name="addons[]" value="<?= (int) $addon['id'] ?>" data-extra data-price="<?= (int) $addon['price_cents'] ?>" data-days="<?= (int) $addon['extra_days'] ?>" <?= in_array((string) $addon['id'], $oldAddons, true) ? 'checked' : '' ?>>
                                <span class="upsell-copy">
                                    <strong><?= e($addon['name']) ?></strong>
                                    <small><?= e($addon['description'] ?: 'Serviço adicional') ?><?= (int) $addon['extra_days'] > 0 ? ' · +' . (int) $addon['extra_days'] . ' dias' : '' ?></small>
                                </span>
                                <span class="upsell-price"><?= e(money($addon['price_cents'])) ?></span>
                                <span class="upsell-add" data-add-label>Adicionar</span>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                <?php endif; ?>
                <div class="buy-cta">
                    <div class="buy-summary">
                        <div class="buy-price">
                            <span class="muted small">Total</span>
                            <strong data-total><?= e(money($firstPackage['price_cents'])) ?></strong>
                            <span class="muted small" data-days-label><?= (int) $firstPackage['hours'] ?>h de vídeo · <?= (int) $firstPackage['delivery_days'] ?> dias</span>
                        </div>
                    </div>
                    <?php if ($preview): ?>
                        <p class="privacy-note">Na pré-visualização a compra fica desativada.</p>
                    <?php elseif (!$authUser): ?>
                        <a class="btn btn-buy btn-block" data-buy-label href="<?= e(url('/login?next=' . rawurlencode('/anuncios/' . $listing['slug'] . '#contratar'))) ?>">Comprar agora <?= e(money($firstPackage['price_cents'])) ?></a>
                        <p class="privacy-note">Entre com a conta da empresa. O pagamento é no checkout transparente do Mercado Pago.</p>
                    <?php elseif (!$isCompany): ?>
                        <p class="privacy-note">Só contas de empresa compram horas. Você está conectado como <?= $authUser->isCreator() ? 'criador' : 'administrador' ?>.</p>
                    <?php else: ?>
                        <?php if (!empty($unpaidContract)): ?>
                            <p class="privacy-note"><a href="<?= e(url('/empresa/contratos/' . $unpaidContract['uuid'] . '/checkout')) ?>">Pagar o pedido <?= e($unpaidContract['code']) ?></a> ou monte outro, com mais adicionais.</p>
                        <?php endif; ?>
                        <?php if ($unavailable): ?>
                            <div class="alert alert-warn mb-0">Agenda fechada até <?= e(fmt_date($listing['unavailable_until'])) ?>. Você ainda pode comprar para depois dessa data.</div>
                        <?php endif; ?>
                        <button class="btn btn-buy btn-block" type="submit" data-buy-label>Comprar agora <?= e(money($firstPackage['price_cents'])) ?></button>
                        <p class="privacy-note">Você vai direto ao checkout transparente do Mercado Pago. O criador só grava depois do pagamento.</p>
                    <?php endif; ?>
                </div>
                <?php if ($isCompany && !$preview): ?>
                    <details class="reveal buy-brief">
                        <summary>Tema e briefing (opcional agora)</summary>
                        <div class="reveal-body">
                            <label class="field<?= error_field('theme') ? ' has-error' : '' ?>"><span>Tema do vídeo</span>
                                <input name="theme" minlength="8" maxlength="300" value="<?= e(old('theme', $listing['title'])) ?>" placeholder="Ex.: Boas-vindas para novos colaboradores">
                                <?php if ($m = error_field('theme')): ?><span class="field-err"><?= e($m) ?></span><?php endif; ?>
                            </label>
                            <label class="field<?= error_field('briefing') ? ' has-error' : '' ?>"><span>Briefing</span>
                                <textarea name="briefing" maxlength="4000" rows="3" placeholder="Objetivo, público, tom…"><?= e(old('briefing')) ?></textarea>
                            </label>
                            <div class="form-grid">
                                <label class="field"><span>Data desejada</span>
                                    <input type="date" name="desired_date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= e(old('desired_date')) ?>">
                                </label>
                                <label class="field"><span>Horário</span>
                                    <input type="time" name="desired_time" value="<?= e(old('desired_time')) ?>">
                                </label>
                            </div>
                        </div>
                    </details>
                <?php endif; ?>
            <?php endif; ?>
        </form>

        <section class="panel" aria-labelledby="criador">
            <div class="seller-hero">
                <?php if (!empty($listing['avatar_path'])): ?>
                    <img class="avatar avatar-lg" src="<?= e(media($listing['avatar_path'])) ?>" alt="">
                <?php else: ?>
                    <span class="avatar avatar-lg" aria-hidden="true"><?= e(initials($listing['display_name'])) ?></span>
                <?php endif; ?>
                <div>
                    <h2 id="criador" class="panel-title mb-0"><?= e(public_first_name($listing['display_name'] ?? '')) ?></h2>
                    <p class="muted small mb-0"><?= e(redact_contact((string) ($listing['headline'] ?? ''))) ?></p>
                    <div class="row" style="margin-top:.35rem">
                        <?php if (!empty($listing['is_verified'])): ?><span class="badge badge-verified">Verificado</span><?php endif; ?>
                        <?php if (is_online($listing['last_seen_at'] ?? null)): ?><span class="badge badge-live">Online agora</span><?php endif; ?>
                    </div>
                </div>
            </div>
            <dl class="facts">
                <div><dt>Avaliação</dt><dd><?= (int) $listing['creator_rating_count'] > 0 ? number_format((float) $listing['creator_rating_avg'], 1, ',', '') . ' (' . (int) $listing['creator_rating_count'] . ')' : '—' ?></dd></div>
                <div><dt>Contratos na plataforma</dt><dd><?= (int) $listing['creator_contracts'] ?></dd></div>
                <div><dt>Antecedência mínima</dt><dd><?= (int) $listing['min_notice_hours'] ?>h</dd></div>
            </dl>
            <a class="btn btn-ghost btn-block" href="<?= e(creator_url($listing['creator_slug'])) ?>">Ver outros anúncios</a>
        </section>

        <?php if ($canAct && $isCompany): ?>
            <details class="reveal">
                <summary>Mandar uma pergunta antes de contratar</summary>
                <form class="reveal-body" method="post" action="<?= e(url('/anuncios/' . $listing['slug'] . '/mensagem')) ?>">
                    <?= csrf_field() ?>
                    <label class="field"><span class="sr-only">Mensagem</span>
                        <textarea name="body" required minlength="2" maxlength="2000" rows="3" placeholder="Escreva sua dúvida sobre o serviço"></textarea>
                    </label>
                    <p class="privacy-note">Telefone, e-mail e redes sociais são ocultados. O combinado vale só dentro da plataforma.</p>
                    <button class="btn btn-ink" type="submit">Enviar mensagem</button>
                </form>
            </details>
        <?php endif; ?>

        <?php if ($canAct && $authUser && !$authUser->isAdmin() && $authUser->id !== (int) $listing['creator_id']): ?>
            <details class="reveal">
                <summary>Denunciar este anúncio</summary>
                <form class="reveal-body" method="post" action="<?= e(url('/denunciar')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="target_type" value="listing">
                    <input type="hidden" name="target" value="<?= e($listing['slug']) ?>">
                    <label class="field"><span>Motivo</span>
                        <select name="reason" required>
                            <?php foreach (ModerationController::REASONS as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field"><span>Detalhes <small>(opcional)</small></span><textarea name="description" maxlength="2000" rows="3"></textarea></label>
                    <button class="btn btn-danger" type="submit">Enviar denúncia</button>
                </form>
            </details>
        <?php endif; ?>
    </aside>
</div>
