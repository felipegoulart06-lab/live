<div class="page-title">
    <div>
        <h1>Olá, <?= e($authUser->firstName()) ?></h1>
        <p>Acompanhe suas solicitações, contratos e avaliações.</p>
    </div>
    <a class="btn btn-accent" href="<?= e(url('/anuncios')) ?>">Encontrar criadores</a>
</div>

<?php foreach ($toReview as $c): ?>
    <div class="alert alert-info">
        <strong>Como foi o trabalho de <?= e($c['creator_name']) ?>?</strong>
        O contrato <?= e($c['code']) ?> foi concluído. <a href="<?= e(url('/empresa/contratos/' . $c['uuid'])) ?>">Avaliar agora</a>
    </div>
<?php endforeach; ?>

<div class="stats">
    <a class="stat" href="<?= e(url('/empresa/solicitacoes?status=pending')) ?>"><span>Aguardando resposta</span><strong><?= (int) $stats['pending_requests'] ?></strong><small>Solicitações enviadas</small></a>
    <a class="stat stat--accent" href="<?= e(url('/empresa/contratos?status=awaiting_payment')) ?>"><span>Aguardando pagamento</span><strong><?= (int) $stats['awaiting_payment'] ?></strong><small>Contratos aceitos</small></a>
    <a class="stat" href="<?= e(url('/empresa/contratos')) ?>"><span>Em andamento</span><strong><?= (int) $stats['open_contracts'] ?></strong><small><?= (int) $stats['completed'] ?> concluídos</small></a>
    <div class="stat"><span>Investido</span><strong><?= e(money($stats['invested'])) ?></strong><small><?= (int) $stats['hours'] ?>h de vídeo entregues</small></div>
</div>

<div class="grid-2">
    <section class="card">
        <div class="card-head"><h2>Solicitações recentes</h2><a href="<?= e(url('/empresa/solicitacoes')) ?>">Ver todas</a></div>
        <?php if ($recentRequests === []): ?>
            <p class="list-empty">Você ainda não enviou solicitações. <a href="<?= e(url('/anuncios')) ?>">Escolha um anúncio</a> para começar.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($recentRequests as $r): ?>
                    <li>
                        <a href="<?= e(url('/empresa/solicitacoes/' . $r['uuid'])) ?>"><strong><?= e($r['creator_name']) ?> · <?= (int) $r['hours'] ?>h</strong><small><?= e($r['listing_title']) ?> · <?= e(money($r['total_cents'])) ?></small></a>
                        <?= status_badge('request', $r['status']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <section class="card">
        <div class="card-head"><h2>Contratos em aberto</h2><a href="<?= e(url('/empresa/contratos')) ?>">Ver todos</a></div>
        <?php if ($openContracts === []): ?>
            <p class="list-empty">Nenhum contrato em aberto.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($openContracts as $c): ?>
                    <li>
                        <a href="<?= e(url('/empresa/contratos/' . $c['uuid'])) ?>"><strong><?= e($c['code']) ?> · <?= e($c['creator_name']) ?></strong><small><?= (int) $c['hours'] ?>h · <?= e(money($c['total_cents'])) ?><?= $c['scheduled_date'] ? ' · ' . e(fmt_date($c['scheduled_date'])) : '' ?></small></a>
                        <?= status_badge('contract', $c['status']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php if ($suggestions !== []): ?>
    <section style="margin-top:1.5rem">
        <div class="section-head"><h2>Bem avaliados</h2><a href="<?= e(url('/anuncios?ordem=avaliacao')) ?>">Ver mais</a></div>
        <div class="card-grid">
            <?php foreach ($suggestions as $item): ?><?= view('listing-card', ['item' => $item]) ?><?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
