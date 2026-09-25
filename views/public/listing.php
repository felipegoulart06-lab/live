<?php
use App\Controllers\Admin\ModerationController;

/** @var array<string, mixed> $listing */
$isCompany = $authUser && $authUser->isCompany();
$canAct = !$preview;
$cover = $images[0] ?? null;
$firstPackage = $packages[0] ?? null;
$oldPackage = (string) old('package_id', $firstPackage['id'] ?? '');
$oldAddons = array_map('strval', (array) old('addons', []));
$location = trim(($listing['city'] ?? '') . ($listing['state'] ? '/' . $listing['state'] : ''), '/');
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
        <p class="lead mb-0"><?= e($listing['short_description']) ?></p>
        <div class="product-meta">
            <?php if ((int) $listing['rating_count'] > 0): ?>
                <span><span class="stars" aria-hidden="true">★</span> <?= number_format((float) $listing['rating_avg'], 1, ',', '') ?> · <?= (int) $listing['rating_count'] ?> avaliações</span>
            <?php else: ?>
                <span>Ainda sem avaliações</span>
            <?php endif; ?>
            <span><?= (int) $listing['contracts_count'] ?> contratos concluídos</span>
            <?php if ($location !== ''): ?><span><?= e($location) ?></span><?php endif; ?>
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
                        <div><h3><?= e($label) ?></h3><p><?= e($listing[$field]) ?></p></div>
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
                    <details><summary><?= e($faq['question']) ?></summary><p><?= e($faq['answer']) ?></p></details>
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
                                <strong><?= e($review['company_name']) ?></strong>
                                <span class="stars" aria-label="<?= (int) $review['rating'] ?> de 5"><?= rating_stars((float) $review['rating']) ?></span>
                                <time datetime="<?= e($review['created_at']) ?>"><?= e(fmt_date($review['created_at'])) ?></time>
                            </header>
                            <?php if (!empty($review['comment'])): ?><p class="mb-0"><?= e($review['comment']) ?></p><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($others !== []): ?>
            <section aria-labelledby="outros">
                <h2 id="outros" class="panel-title">Outros anúncios de <?= e($listing['display_name']) ?></h2>
                <div class="card-grid card-grid--3">
                    <?php foreach ($others as $item): ?><?= view('listing-card', ['item' => $item]) ?><?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <aside class="stack">
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
                <div class="buy-price">
                    <span class="muted small">Total</span>
                    <strong data-total><?= e(money($firstPackage['price_cents'])) ?></strong>
                    <span class="muted small" data-days-label><?= (int) $firstPackage['hours'] ?>h de vídeo · <?= (int) $firstPackage['delivery_days'] ?> dias</span>
                </div>
                <?php if ($addons !== []): ?>
                    <fieldset class="extras">
                        <legend>Adicionais</legend>
                        <?php foreach ($addons as $addon): ?>
                            <label class="extra">
                                <input type="checkbox" name="addons[]" value="<?= (int) $addon['id'] ?>" data-extra data-price="<?= (int) $addon['price_cents'] ?>" data-days="<?= (int) $addon['extra_days'] ?>" <?= in_array((string) $addon['id'], $oldAddons, true) ? 'checked' : '' ?>>
                                <span><?= e($addon['name']) ?> · <strong><?= e(money($addon['price_cents'])) ?></strong>
                                    <small><?= e($addon['description'] ?? '') ?><?= (int) $addon['extra_days'] > 0 ? ' · +' . (int) $addon['extra_days'] . ' dias' : '' ?></small></span>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                <?php endif; ?>

                <?php if ($preview): ?>
                    <p class="privacy-note">Na pré-visualização a contratação fica desativada.</p>
                <?php elseif (!$authUser): ?>
                    <a class="btn btn-accent btn-block" href="<?= e(url('/login')) ?>">Entrar para contratar</a>
                    <p class="privacy-note">Ainda não tem conta? <a href="<?= e(url('/cadastro?tipo=empresa')) ?>">Cadastre sua empresa</a>.</p>
                <?php elseif (!$isCompany): ?>
                    <p class="privacy-note">Só contas de empresa contratam horas. Você está conectado como <?= $authUser->isCreator() ? 'criador' : 'administrador' ?>.</p>
                <?php elseif ($pendingRequest): ?>
                    <div class="alert alert-info mb-0">Você já tem a solicitação <?= e($pendingRequest['code']) ?> aguardando resposta. <a href="<?= e(url('/empresa/solicitacoes/' . $pendingRequest['uuid'])) ?>">Acompanhar</a></div>
                <?php else: ?>
                    <?php if ($unavailable): ?>
                        <div class="alert alert-warn mb-0">O criador está indisponível até <?= e(fmt_date($listing['unavailable_until'])) ?>. Você pode solicitar para depois dessa data.</div>
                    <?php endif; ?>
                    <label class="field<?= error_field('theme') ? ' has-error' : '' ?>"><span>Tema do vídeo</span>
                        <input name="theme" required minlength="8" maxlength="300" value="<?= e(old('theme')) ?>" placeholder="Ex.: Boas-vindas para novos colaboradores">
                        <?php if ($m = error_field('theme')): ?><span class="field-err"><?= e($m) ?></span><?php endif; ?>
                    </label>
                    <label class="field<?= error_field('briefing') ? ' has-error' : '' ?>"><span>Briefing <small>(opcional)</small></span>
                        <textarea name="briefing" maxlength="4000" rows="4" placeholder="Objetivo, público, tom, o que não pode ser dito…"><?= e(old('briefing')) ?></textarea>
                        <?php if ($m = error_field('briefing')): ?><span class="field-err"><?= e($m) ?></span><?php endif; ?>
                    </label>
                    <div class="form-grid">
                        <label class="field<?= error_field('desired_date') ? ' has-error' : '' ?>"><span>Data desejada</span>
                            <input type="date" name="desired_date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= e(old('desired_date')) ?>">
                            <?php if ($m = error_field('desired_date')): ?><span class="field-err"><?= e($m) ?></span><?php endif; ?>
                        </label>
                        <label class="field<?= error_field('desired_time') ? ' has-error' : '' ?>"><span>Horário</span>
                            <input type="time" name="desired_time" value="<?= e(old('desired_time')) ?>">
                            <?php if ($m = error_field('desired_time')): ?><span class="field-err"><?= e($m) ?></span><?php endif; ?>
                        </label>
                    </div>
                    <label class="field"><span>Mensagem para o criador <small>(opcional)</small></span>
                        <textarea name="message" maxlength="2000" rows="3"><?= e(old('message')) ?></textarea>
                    </label>
                    <button class="btn btn-accent btn-block" type="submit">Enviar solicitação</button>
                    <p class="privacy-note">O criador tem até <?= (int) setting('request_expiry_hours', 72) ?> horas para responder. Você só paga depois que ele aceitar.</p>
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
                    <h2 id="criador" class="panel-title mb-0"><?= e($listing['display_name']) ?></h2>
                    <p class="muted small mb-0"><?= e($listing['headline'] ?? '') ?></p>
                    <div class="row" style="margin-top:.35rem">
                        <?php if (!empty($listing['is_verified'])): ?><span class="badge badge-verified">Verificado</span><?php endif; ?>
                        <?php if (is_online($listing['last_seen_at'] ?? null)): ?><span class="badge badge-live">Online agora</span><?php endif; ?>
                    </div>
                </div>
            </div>
            <dl class="facts">
                <div><dt>Avaliação</dt><dd><?= (int) $listing['creator_rating_count'] > 0 ? number_format((float) $listing['creator_rating_avg'], 1, ',', '') . ' (' . (int) $listing['creator_rating_count'] . ')' : '—' ?></dd></div>
                <div><dt>Contratos</dt><dd><?= (int) $listing['creator_contracts'] ?></dd></div>
                <div><dt>Experiência</dt><dd><?= !empty($listing['experience_years']) ? (int) $listing['experience_years'] . ' anos' : '—' ?></dd></div>
                <div><dt>Na plataforma desde</dt><dd><?= e(date('m/Y', strtotime((string) $listing['member_since']))) ?></dd></div>
                <div><dt>Antecedência mínima</dt><dd><?= (int) $listing['min_notice_hours'] ?>h</dd></div>
            </dl>
            <?php if ($availability !== []): ?>
                <p class="small" style="margin-top:1rem"><strong>Disponibilidade</strong><br>
                    <?php foreach ($availability as $slot): ?>
                        <?= e(weekday_name((int) $slot['weekday'])) ?> <?= e(substr($slot['start_time'], 0, 5)) ?>–<?= e(substr($slot['end_time'], 0, 5)) ?><br>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>
            <a class="btn btn-ghost btn-block" href="<?= e(creator_url($listing['creator_slug'])) ?>">Ver perfil do criador</a>
        </section>

        <?php if ($canAct && $isCompany): ?>
            <details class="reveal">
                <summary>Mandar uma pergunta antes de contratar</summary>
                <form class="reveal-body" method="post" action="<?= e(url('/anuncios/' . $listing['slug'] . '/mensagem')) ?>">
                    <?= csrf_field() ?>
                    <label class="field"><span class="sr-only">Mensagem</span>
                        <textarea name="body" required minlength="2" maxlength="2000" rows="3" placeholder="Escreva sua dúvida sobre o serviço"></textarea>
                    </label>
                    <p class="privacy-note">Telefones, e-mails e links de contato externo são ocultados automaticamente.</p>
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
