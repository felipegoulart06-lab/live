<?php
$listPath = $isCreator ? '/admin/criadores' : '/admin/empresas';
$name = $isCreator ? $u['display_name'] : $u['company_name'];
?>
<a class="back-link" href="<?= e(url($listPath)) ?>">← <?= $isCreator ? 'Criadores' : 'Empresas' ?></a>
<div class="page-title">
    <div class="person">
        <?php if ($u['avatar_path']): ?><img class="avatar avatar-lg" src="<?= e(media($u['avatar_path'])) ?>" alt=""><?php else: ?><span class="avatar avatar-lg" aria-hidden="true"><?= e(initials($name)) ?></span><?php endif; ?>
        <div>
            <h1><?= e($name) ?></h1>
            <p class="row">
                <?= status_badge('user', $u['status']) ?>
                <?php if ($isCreator && (int) $u['is_verified'] === 1): ?><span class="badge badge-verified">Verificado</span><?php endif; ?>
                <span><?= is_online($u['last_seen_at']) ? 'Online agora' : 'Visto ' . e(time_ago($u['last_seen_at'])) ?></span>
            </p>
        </div>
    </div>
    <?php if ($isCreator && $u['status'] === 'active'): ?><a class="btn btn-ghost" href="<?= e(creator_url($u['slug'])) ?>">Perfil público</a><?php endif; ?>
</div>

<?php if ($u['status'] === 'blocked'): ?>
    <div class="reason-box" style="margin-bottom:1rem"><strong>Conta bloqueada</strong><p class="mb-0 pre"><?= e($u['blocked_reason'] ?? '') ?></p></div>
<?php endif; ?>

<div class="stats stats--3">
    <div class="stat"><span><?= $isCreator ? 'Faturado (bruto)' : 'Investido' ?></span><strong><?= e(money($money['gross'])) ?></strong></div>
    <?php if ($isCreator): ?>
        <div class="stat"><span>Aguardando repasse</span><strong><?= e(money($money['awaiting_payout'])) ?></strong></div>
        <div class="stat"><span>Repassado</span><strong><?= e(money($money['paid_out'])) ?></strong></div>
    <?php else: ?>
        <div class="stat"><span>Contratos</span><strong><?= count($contracts) ?><?= count($contracts) === 10 ? '+' : '' ?></strong></div>
        <div class="stat"><span>Solicitações</span><strong><?= count($requests) ?><?= count($requests) === 10 ? '+' : '' ?></strong></div>
    <?php endif; ?>
</div>

<div class="grid-main">
    <div class="stack">
        <?php if ($isCreator): ?>
            <section class="card">
                <div class="card-head"><h2>Anúncios</h2></div>
                <?php if ($listings === []): ?>
                    <p class="list-empty">Nenhum anúncio.</p>
                <?php else: ?>
                    <ul class="list">
                        <?php foreach ($listings as $l): ?>
                            <li><a href="<?= e(url('/admin/anuncios/' . $l['uuid'])) ?>"><strong><?= e($l['title']) ?></strong><small><?= (int) $l['starting_price_cents'] > 0 ? 'A partir de ' . e(money($l['starting_price_cents'])) . ' · ' : '' ?><?= (int) $l['contracts_count'] ?> contratos</small></a><?= status_badge('listing', $l['status']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="card">
            <div class="card-head"><h2>Contratos recentes</h2><a href="<?= e(url('/admin/contratos?q=' . rawurlencode($name))) ?>">Ver todos</a></div>
            <?php if ($contracts === []): ?>
                <p class="list-empty">Nenhum contrato.</p>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($contracts as $c): ?>
                        <li><a href="<?= e(url('/admin/contratos/' . $c['uuid'])) ?>"><strong><?= e($c['code']) ?> · <?= e($isCreator ? $c['company_name'] : $c['creator_name']) ?></strong><small><?= e($c['listing_title']) ?> · <?= e(money($c['total_cents'])) ?> · <?= e(fmt_date($c['created_at'])) ?></small></a><?= status_badge('contract', $c['status']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card-head"><h2>Solicitações recentes</h2></div>
            <?php if ($requests === []): ?>
                <p class="list-empty">Nenhuma solicitação.</p>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($requests as $r): ?>
                        <li><span><strong><?= e($r['code']) ?></strong><small><?= e($r['listing_title']) ?> · <?= e(money($r['total_cents'])) ?> · <?= e(fmt_date($r['created_at'])) ?></small></span><?= status_badge('request', $r['status']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <?php if ($isCreator): ?>
            <section class="card">
                <div class="card-head"><h2>Avaliações recebidas</h2><a href="<?= e(url('/admin/avaliacoes')) ?>">Moderar</a></div>
                <?php if ($reviews === []): ?>
                    <p class="list-empty">Nenhuma avaliação.</p>
                <?php else: ?>
                    <ul class="list">
                        <?php foreach ($reviews as $rv): ?>
                            <li><span><strong><?= e($rv['company_name']) ?> · <span class="stars"><?= rating_stars((float) $rv['rating']) ?></span></strong><small><?= e(mb_substr((string) $rv['comment'], 0, 140)) ?></small></span><?= status_badge('review', $rv['status']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="card">
            <div class="card-head"><h2>Atividade</h2></div>
            <?php if ($activity === []): ?>
                <p class="list-empty">Sem atividade registrada.</p>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($activity as $a): ?>
                        <li><span><strong><?= e($a['description']) ?></strong><small><?= e($a['ip'] ?? '') ?></small></span><small class="nowrap"><?= e(fmt_datetime($a['created_at'])) ?></small></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <div class="stack">
        <section class="card card-pad">
            <h2 class="panel-title">Cadastro</h2>
            <dl class="kv">
                <dt>E-mail</dt><dd><?= e($u['email']) ?></dd>
                <?php if ($isCreator): ?>
                    <dt>Telefone</dt><dd><?= e($u['phone'] ?: '—') ?></dd>
                    <dt>Título</dt><dd><?= e($u['headline'] ?: '—') ?></dd>
                    <dt>Horas vendidas</dt><dd><?= (int) $u['hours_sold'] ?>h</dd>
                    <dt>Avaliação</dt><dd><?= (int) $u['rating_count'] > 0 ? number_format((float) $u['rating_avg'], 1, ',', '') . ' (' . (int) $u['rating_count'] . ')' : '—' ?></dd>
                <?php else: ?>
                    <dt>Responsável</dt><dd><?= e($u['responsible_name'] ?: $u['display_name']) ?></dd>
                    <dt>Razão social</dt><dd><?= e($u['legal_name'] ?: '—') ?></dd>
                    <dt>CNPJ</dt><dd><?= e($u['document'] ?: '—') ?></dd>
                    <dt>Telefone</dt><dd><?= e($u['company_phone'] ?: ($u['phone'] ?: '—')) ?></dd>
                    <dt>Site</dt><dd><?= $u['website'] ? '<a href="' . e($u['website']) . '" rel="noopener noreferrer nofollow" target="_blank">' . e($u['website']) . '</a>' : '—' ?></dd>
                <?php endif; ?>
                <dt>Cidade</dt><dd><?= e(trim(($u['city'] ?? '') . ($u['state'] ? '/' . $u['state'] : ''), '/') ?: '—') ?></dd>
                <dt>Cadastro</dt><dd><?= e(fmt_datetime($u['created_at'])) ?></dd>
                <dt>Último login</dt><dd><?= e(fmt_datetime($u['last_login_at'])) ?><?= $u['last_login_ip'] ? ' · ' . e($u['last_login_ip']) : '' ?></dd>
            </dl>
        </section>

        <?php if ($isCreator): ?>
            <section class="card card-pad">
                <h2 class="panel-title">Verificação</h2>
                <?php if ($u['verification_requested_at'] && (int) $u['is_verified'] === 0): ?><p class="alert alert-info">Pedido enviado em <?= e(fmt_date($u['verification_requested_at'])) ?>.</p><?php endif; ?>
                <form method="post" action="<?= e(url('/admin/criadores/' . $u['uuid'] . '/verificar')) ?>" class="row">
                    <?= csrf_field() ?>
                    <?php if ((int) $u['is_verified'] === 1): ?>
                        <button class="btn btn-ghost" type="submit" name="value" value="0">Remover selo</button>
                    <?php else: ?>
                        <button class="btn btn-accent" type="submit" name="value" value="1">Conceder selo de verificado</button>
                        <?php if ($u['verification_requested_at']): ?><button class="btn btn-ghost" type="submit" name="value" value="0">Negar pedido</button><?php endif; ?>
                    <?php endif; ?>
                </form>
            </section>
        <?php endif; ?>

        <section class="card card-pad">
            <h2 class="panel-title">Acesso</h2>
            <?php if ($u['status'] === 'active'): ?>
                <form method="post" action="<?= e(url('/admin/usuarios/' . $u['uuid'] . '/bloquear')) ?>" class="stack-sm" data-confirm="Bloquear esta conta? As sessões abertas serão encerradas.">
                    <?= csrf_field() ?>
                    <?= view('field', ['name' => 'reason', 'label' => 'Motivo do bloqueio', 'type' => 'textarea', 'hint' => $isCreator ? 'Os anúncios ativos e pendentes serão pausados.' : 'A empresa verá este motivo ao tentar entrar.', 'attrs' => 'required minlength="5" maxlength="1000" rows="3"']) ?>
                    <button class="btn btn-danger" type="submit">Bloquear conta</button>
                </form>
            <?php else: ?>
                <form method="post" action="<?= e(url('/admin/usuarios/' . $u['uuid'] . '/desbloquear')) ?>"><?= csrf_field() ?><button class="btn btn-accent" type="submit">Desbloquear conta</button></form>
            <?php endif; ?>
        </section>
    </div>
</div>
