<?php
use App\Controllers\Admin\ModerationController;
?>
<nav class="crumb" aria-label="Você está em">
    <a href="<?= e(url('/')) ?>">Início</a><span aria-hidden="true">/</span><a href="<?= e(url('/anuncios')) ?>">Anúncios</a><span aria-hidden="true">/</span><span><?= e(public_first_name($creator['display_name'] ?? '')) ?></span>
</nav>

<div class="product-grid">
    <div class="stack">
        <section class="panel">
            <div class="seller-hero">
                <?php if (!empty($creator['avatar_path'])): ?>
                    <img class="avatar avatar-lg" src="<?= e(media($creator['avatar_path'])) ?>" alt="">
                <?php else: ?>
                    <span class="avatar avatar-lg" aria-hidden="true"><?= e(initials($creator['display_name'])) ?></span>
                <?php endif; ?>
                <div>
                    <h1 class="mb-0" style="font-size:2rem"><?= e(public_first_name($creator['display_name'] ?? '')) ?></h1>
                    <p class="muted mb-0"><?= e(redact_contact((string) ($creator['headline'] ?? ''))) ?></p>
                    <div class="row" style="margin-top:.4rem">
                        <?php if (!empty($creator['is_verified'])): ?><span class="badge badge-verified">Verificado</span><?php endif; ?>
                    </div>
                </div>
            </div>
            <p class="privacy-note mb-0">Este espaço mostra os anúncios de horas de vídeo. Contato, redes e dados pessoais não são publicados.</p>
        </section>

        <section aria-labelledby="anuncios">
            <h2 id="anuncios" class="panel-title">Anúncios</h2>
            <div class="card-grid card-grid--3">
                <?php foreach ($listings as $item): ?><?= view('listing-card', ['item' => $item]) ?><?php endforeach; ?>
            </div>
        </section>

        <section class="panel" aria-labelledby="avaliacoes">
            <h2 id="avaliacoes" class="panel-title">Avaliações de empresas</h2>
            <?php if ($reviews === []): ?>
                <p class="muted mb-0">Ainda não há avaliações neste perfil de serviço.</p>
            <?php else: ?>
                <div class="review-list">
                    <?php foreach ($reviews as $review): ?>
                        <article class="review-card">
                            <header>
                                <strong><?= e(public_company_label()) ?></strong>
                                <span class="stars" aria-label="<?= (int) $review['rating'] ?> de 5"><?= rating_stars((float) $review['rating']) ?></span>
                                <span><?= e($review['listing_title']) ?></span>
                                <time datetime="<?= e($review['created_at']) ?>"><?= e(fmt_date($review['created_at'])) ?></time>
                            </header>
                            <?php if (!empty($review['comment'])): ?><p class="mb-0"><?= e(redact_contact((string) $review['comment'])) ?></p><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <aside class="stack">
        <section class="panel">
            <dl class="facts" style="margin-top:0">
                <div><dt>Avaliação</dt><dd><?= (int) $creator['rating_count'] > 0 ? number_format((float) $creator['rating_avg'], 1, ',', '') . ' (' . (int) $creator['rating_count'] . ')' : '—' ?></dd></div>
                <div><dt>Contratos na plataforma</dt><dd><?= (int) $creator['contracts_count'] ?></dd></div>
                <div><dt>Horas vendidas aqui</dt><dd><?= (int) $creator['hours_sold'] ?>h</dd></div>
            </dl>
            <?php if (!empty($creator['unavailable_until']) && $creator['unavailable_until'] >= date('Y-m-d')): ?>
                <div class="alert alert-warn" style="margin:1rem 0 0">Indisponível para novos contratos até <?= e(fmt_date($creator['unavailable_until'])) ?></div>
            <?php endif; ?>
        </section>
        <?php if ($authUser): ?>
            <form method="post" action="<?= e(url('/favoritos')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="creator">
                <input type="hidden" name="slug" value="<?= e($creator['slug']) ?>">
                <button class="btn btn-ghost btn-block" type="submit" aria-pressed="<?= $favorited ? 'true' : 'false' ?>"><?= $favorited ? 'Salvo nos favoritos' : 'Salvar anúncios' ?></button>
            </form>
            <?php if (!$authUser->isAdmin() && $authUser->id !== (int) $creator['id']): ?>
                <details class="reveal">
                    <summary>Denunciar</summary>
                    <form class="reveal-body" method="post" action="<?= e(url('/denunciar')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="target_type" value="creator">
                        <input type="hidden" name="target" value="<?= e($creator['slug']) ?>">
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
        <?php else: ?>
            <a class="btn btn-accent btn-block" href="<?= e(url('/login')) ?>">Entrar para contratar</a>
        <?php endif; ?>
    </aside>
</div>
