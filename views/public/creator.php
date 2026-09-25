<?php
use App\Controllers\Admin\ModerationController;

$location = trim(($creator['city'] ?? '') . ($creator['state'] ? '/' . $creator['state'] : ''), '/');
$specialties = array_filter(array_map('trim', explode(',', (string) ($creator['specialties'] ?? ''))));
?>
<nav class="crumb" aria-label="Você está em">
    <a href="<?= e(url('/')) ?>">Início</a><span aria-hidden="true">/</span><a href="<?= e(url('/criadores')) ?>">Criadores</a><span aria-hidden="true">/</span><span><?= e($creator['display_name']) ?></span>
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
                    <h1 class="mb-0" style="font-size:2rem"><?= e($creator['display_name']) ?></h1>
                    <p class="muted mb-0"><?= e($creator['headline'] ?? '') ?></p>
                    <div class="row" style="margin-top:.4rem">
                        <?php if (!empty($creator['is_verified'])): ?><span class="badge badge-verified">Verificado</span><?php endif; ?>
                        <?php if (is_online($creator['last_seen_at'] ?? null)): ?><span class="badge badge-live">Online agora</span><?php endif; ?>
                        <?php if ($location !== ''): ?><span class="badge"><?= e($location) ?></span><?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if (!empty($creator['bio'])): ?><div class="prose"><?= nl2p($creator['bio']) ?></div><?php endif; ?>
            <?php if ($specialties !== []): ?>
                <div class="chips"><?php foreach ($specialties as $s): ?><span class="chip"><?= e($s) ?></span><?php endforeach; ?></div>
            <?php endif; ?>
        </section>

        <section aria-labelledby="anuncios">
            <h2 id="anuncios" class="panel-title">Anúncios</h2>
            <div class="card-grid card-grid--3">
                <?php foreach ($listings as $item): ?><?= view('listing-card', ['item' => $item]) ?><?php endforeach; ?>
            </div>
        </section>

        <section class="panel" aria-labelledby="avaliacoes">
            <h2 id="avaliacoes" class="panel-title">Avaliações</h2>
            <?php if ($reviews === []): ?>
                <p class="muted mb-0">Ainda não há avaliações para este criador.</p>
            <?php else: ?>
                <div class="review-list">
                    <?php foreach ($reviews as $review): ?>
                        <article class="review-card">
                            <header>
                                <strong><?= e($review['company_name']) ?></strong>
                                <span class="stars" aria-label="<?= (int) $review['rating'] ?> de 5"><?= rating_stars((float) $review['rating']) ?></span>
                                <span><?= e($review['listing_title']) ?></span>
                                <time datetime="<?= e($review['created_at']) ?>"><?= e(fmt_date($review['created_at'])) ?></time>
                            </header>
                            <?php if (!empty($review['comment'])): ?><p class="mb-0"><?= e($review['comment']) ?></p><?php endif; ?>
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
                <div><dt>Contratos</dt><dd><?= (int) $creator['contracts_count'] ?></dd></div>
                <div><dt>Horas vendidas</dt><dd><?= (int) $creator['hours_sold'] ?>h</dd></div>
                <div><dt>Experiência</dt><dd><?= !empty($creator['experience_years']) ? (int) $creator['experience_years'] . ' anos' : '—' ?></dd></div>
                <div><dt>Antecedência</dt><dd><?= (int) $creator['min_notice_hours'] ?>h</dd></div>
                <div><dt>Desde</dt><dd><?= e(date('m/Y', strtotime((string) $creator['member_since']))) ?></dd></div>
            </dl>
            <?php if (!empty($creator['unavailable_until']) && $creator['unavailable_until'] >= date('Y-m-d')): ?>
                <div class="alert alert-warn" style="margin:1rem 0 0">Indisponível até <?= e(fmt_date($creator['unavailable_until'])) ?><?= !empty($creator['unavailable_note']) ? ': ' . e($creator['unavailable_note']) : '' ?></div>
            <?php endif; ?>
            <?php if ($availability !== []): ?>
                <p class="small" style="margin:1rem 0 0"><strong>Disponibilidade</strong><br>
                    <?php foreach ($availability as $slot): ?>
                        <?= e(weekday_name((int) $slot['weekday'])) ?> <?= e(substr($slot['start_time'], 0, 5)) ?>–<?= e(substr($slot['end_time'], 0, 5)) ?><br>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>
        </section>
        <?php if ($authUser): ?>
            <form method="post" action="<?= e(url('/favoritos')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="creator">
                <input type="hidden" name="slug" value="<?= e($creator['slug']) ?>">
                <button class="btn btn-ghost btn-block" type="submit" aria-pressed="<?= $favorited ? 'true' : 'false' ?>"><?= $favorited ? 'Salvo nos favoritos' : 'Salvar criador' ?></button>
            </form>
            <?php if (!$authUser->isAdmin() && $authUser->id !== (int) $creator['id']): ?>
                <details class="reveal">
                    <summary>Denunciar este perfil</summary>
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
