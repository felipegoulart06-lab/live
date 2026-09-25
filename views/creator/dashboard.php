<?php
$pendingOnboarding = array_filter($onboarding, static fn (array $item): bool => !$item['done']);
$acceptRate = $stats['total_requests'] > 0 ? round($stats['accepted_requests'] / $stats['total_requests'] * 100) : null;
?>
<div class="page-title">
    <div>
        <h1>Olá, <?= e($authUser->firstName()) ?>.</h1>
        <p>Veja como estão seus anúncios e trabalhos.</p>
    </div>
    <a class="btn btn-accent" href="<?= e(url('/painel/anuncios/novo')) ?>">Novo anúncio</a>
</div>

<?php foreach ($rejectedListings as $item): ?>
    <div class="alert alert-err">
        <strong>"<?= e($item['title']) ?>" foi reprovado.</strong>
        Motivo: <?= e($item['rejection_reason']) ?> <a href="<?= e(url('/painel/anuncios/' . $item['uuid'])) ?>">Corrigir e reenviar</a>
    </div>
<?php endforeach; ?>

<div class="stats">
    <a class="stat" href="<?= e(url('/painel/anuncios?status=active')) ?>"><span>Anúncios ativos</span><strong><?= (int) $listings['active'] ?></strong><small><?= (int) $listings['pending'] ?> em revisão · <?= (int) $listings['drafts'] ?> rascunhos</small></a>
    <a class="stat stat--accent" href="<?= e(url('/painel/solicitacoes?status=pending')) ?>"><span>Solicitações</span><strong><?= (int) $stats['pending_requests'] ?></strong><small>Aguardando a sua resposta</small></a>
    <a class="stat" href="<?= e(url('/painel/contratos')) ?>"><span>Contratos ativos</span><strong><?= (int) $stats['open_contracts'] ?></strong><small><?= (int) $creator['contracts_count'] ?> concluídos</small></a>
    <a class="stat" href="<?= e(url('/painel/financeiro')) ?>"><span>A receber</span><strong><?= e(money($money['to_receive'])) ?></strong><small><?= e(money($money['earned'])) ?> já recebido</small></a>
</div>

<div class="grid-main">
    <div class="stack">
        <section class="card">
            <div class="card-head"><h2>Solicitações recentes</h2><a href="<?= e(url('/painel/solicitacoes')) ?>">Ver todas</a></div>
            <?php if ($recentRequests === []): ?>
                <p class="list-empty">Nenhuma solicitação ainda. Quando uma empresa pedir horas em um anúncio ativo, ela aparece aqui.</p>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($recentRequests as $r): ?>
                        <li>
                            <a href="<?= e(url('/painel/solicitacoes/' . $r['uuid'])) ?>">
                                <strong><?= e($r['company_name']) ?> · <?= (int) $r['hours'] ?>h</strong>
                                <small><?= e($r['listing_title']) ?> · <?= e(money($r['total_cents'])) ?><?= $r['status'] === 'pending' ? ' · expira ' . e(fmt_datetime($r['expires_at'])) : '' ?></small>
                            </a>
                            <?= status_badge('request', $r['status']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card-head"><h2>Próximos trabalhos</h2><a href="<?= e(url('/painel/contratos')) ?>">Contratos</a></div>
            <?php if ($upcoming === []): ?>
                <p class="list-empty">Nenhum contrato em aberto.</p>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($upcoming as $c): ?>
                        <li>
                            <a href="<?= e(url('/painel/contratos/' . $c['uuid'])) ?>">
                                <strong><?= e($c['code']) ?> · <?= e($c['company_name']) ?></strong>
                                <small><?= (int) $c['hours'] ?>h · <?= $c['scheduled_date'] ? 'gravação em ' . e(fmt_date($c['scheduled_date'])) . ($c['scheduled_time'] ? ' às ' . e(substr($c['scheduled_time'], 0, 5)) : '') : 'data a combinar' ?></small>
                            </a>
                            <?= status_badge('contract', $c['status']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <div class="stack">
        <?php if ($pendingOnboarding !== []): ?>
            <section class="card card-pad">
                <h2 class="panel-title">Primeiros passos</h2>
                <ul class="checklist">
                    <?php foreach ($onboarding as $item): ?>
                        <li class="<?= $item['done'] ? 'done' : '' ?>">
                            <span class="tick" aria-hidden="true"><?= $item['done'] ? '✓' : '' ?></span>
                            <?php if ($item['done']): ?>
                                <span><?= e($item['label']) ?> <span class="sr-only">(feito)</span></span>
                            <?php else: ?>
                                <a href="<?= e(url($item['href'])) ?>"><?= e($item['label']) ?></a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <section class="card card-pad">
            <h2 class="panel-title">Seus anúncios</h2>
            <dl class="kv">
                <dt>Ativos</dt><dd><?= (int) $listings['active'] ?></dd>
                <dt>Em revisão</dt><dd><?= (int) $listings['pending'] ?></dd>
                <dt>Rascunhos</dt><dd><?= (int) $listings['drafts'] ?></dd>
                <dt>Visualizações</dt><dd><?= (int) $listings['views'] ?></dd>
            </dl>
            <p style="margin:1rem 0 0"><a class="btn btn-ghost btn-sm" href="<?= e(url('/painel/anuncios')) ?>">Gerenciar anúncios</a></p>
        </section>

        <section class="card card-pad">
            <h2 class="panel-title">Seu desempenho</h2>
            <dl class="kv">
                <dt>Visualizações</dt><dd><?= (int) $listings['views'] ?></dd>
                <dt>Contatos</dt><dd><?= (int) $listings['contacts'] ?></dd>
                <dt>Contratações</dt><dd><?= (int) $creator['contracts_count'] ?></dd>
                <dt>Taxa de contratação</dt><dd><?= (int) $listings['contacts'] > 0 ? round((int) $creator['contracts_count'] / (int) $listings['contacts'] * 100) . '%' : '—' ?></dd>
            </dl>
            <?php if (!empty($perfMonths)): ?>
                <?php $maxPerf = max(1, ...array_values($perfMonths)); ?>
                <div class="bars bars-sm" role="img" aria-label="Contratos nos últimos seis meses">
                    <?php foreach ($perfMonths as $label => $n): ?>
                        <div class="bar">
                            <span class="bar-fill" style="height:<?= max(2, (int) round($n / $maxPerf * 100)) ?>%"></span>
                            <b><?= (int) $n ?></b>
                            <small><?= e(substr($label, 5, 2)) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
