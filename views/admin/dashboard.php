<?php
use App\Controllers\Admin\ModerationController;

$t = $totals;
$monthNames = ['01' => 'jan', '02' => 'fev', '03' => 'mar', '04' => 'abr', '05' => 'mai', '06' => 'jun', '07' => 'jul', '08' => 'ago', '09' => 'set', '10' => 'out', '11' => 'nov', '12' => 'dez'];
$values = array_column($months, $chart);
$max = max(1, ...$values);
$chartLabels = ['contratos' => 'Contratos', 'anuncios' => 'Anúncios', 'usuarios' => 'Usuários', 'faturamento' => 'Faturamento'];
$qs = static fn (array $extra): string => '?' . http_build_query(array_filter(array_merge([
    'periodo' => $period !== 'personalizado' ? $period : null,
    'de' => $from,
    'ate' => $to,
    'grafico' => $chart,
], $extra), static fn ($v) => $v !== null && $v !== ''));
?>
<div class="page-title">
    <div>
        <h1>Visão geral</h1>
        <p>Veja o que está acontecendo no CinquentaConto.</p>
    </div>
    <nav class="tabs mb-0" aria-label="Período" style="border:0">
        <a href="<?= e(url('/admin' . $qs(['periodo' => 'hoje', 'de' => null, 'ate' => null]))) ?>" class="<?= $period === 'hoje' ? 'is-active' : '' ?>">Hoje</a>
        <a href="<?= e(url('/admin' . $qs(['periodo' => '7', 'de' => null, 'ate' => null]))) ?>" class="<?= $period === '7' ? 'is-active' : '' ?>">7 dias</a>
        <a href="<?= e(url('/admin' . $qs(['periodo' => '30', 'de' => null, 'ate' => null]))) ?>" class="<?= $period === '30' ? 'is-active' : '' ?>">30 dias</a>
        <a href="#periodo-personalizado" class="<?= $period === 'personalizado' ? 'is-active' : '' ?>">Personalizado</a>
    </nav>
</div>

<form id="periodo-personalizado" class="toolbar period-form" method="get" action="<?= e(url('/admin')) ?>">
    <input type="hidden" name="grafico" value="<?= e($chart) ?>">
    <label class="field"><span>De</span><input type="date" name="de" value="<?= e((string) $from) ?>"></label>
    <label class="field"><span>Até</span><input type="date" name="ate" value="<?= e((string) $to) ?>"></label>
    <button class="btn btn-ghost" type="submit">Aplicar período</button>
</form>

<div class="stats stats--6">
    <a class="stat" href="<?= e(url('/admin/criadores')) ?>"><span>Usuários</span><strong><?= (int) $t['users'] ?></strong><small><?= (int) $t['new_users'] ?> novos no período</small></a>
    <a class="stat" href="<?= e(url('/admin/criadores')) ?>"><span>Criadores</span><strong><?= (int) $t['creators'] ?></strong><small><?= (int) $t['companies'] ?> empresas</small></a>
    <a class="stat" href="<?= e(url('/admin/anuncios?status=active')) ?>"><span>Anúncios ativos</span><strong><?= (int) $t['active_listings'] ?></strong><small><?= (int) $t['pending_listings'] ?> na fila</small></a>
    <a class="stat" href="<?= e(url('/admin/contratos')) ?>"><span>Contratos</span><strong><?= (int) $t['contracts'] ?></strong><small><?= (int) $t['running_contracts'] ?> em andamento</small></a>
    <div class="stat"><span>Valor movimentado</span><strong><?= e(money($t['gmv'])) ?></strong><small>Pagamentos confirmados</small></div>
    <a class="stat stat--accent" href="<?= e(url('/admin/anuncios/pendentes')) ?>"><span>Pendências</span><strong><?= (int) $attentionCount ?></strong><small>Itens que pedem ação</small></a>
</div>

<div class="grid-main">
    <div class="stack">
        <section class="card card-pad">
            <div class="row-between">
                <h2 class="panel-title mb-0">Atividade</h2>
                <nav class="tabs mb-0" aria-label="Série do gráfico" style="border:0">
                    <?php foreach ($chartLabels as $key => $label): ?>
                        <a href="<?= e(url('/admin' . $qs(['grafico' => $key]))) ?>" class="<?= $chart === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>
            <div class="bars" role="img" aria-label="<?= e($chartLabels[$chart]) ?> nos últimos seis meses">
                <?php foreach ($months as $m): ?>
                    <?php $value = (int) $m[$chart]; ?>
                    <div class="bar">
                        <span class="bar-fill" style="height:<?= max(2, (int) round($value / $max * 100)) ?>%"></span>
                        <b><?= $chart === 'faturamento' ? e(money($value)) : $value ?></b>
                        <small><?= e($monthNames[substr($m['label'], 5, 2)] ?? $m['label']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card">
            <div class="card-head"><h2>Atividade recente</h2><a href="<?= e(url('/admin/atividade')) ?>">Ver auditoria</a></div>
            <?php if ($recentActions === []): ?>
                <p class="list-empty">Nenhuma ação registrada.</p>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($recentActions as $a): ?>
                        <li>
                            <span>
                                <strong><?= e($a['display_name'] ?? 'Sistema') ?></strong>
                                <small><?= e($a['description']) ?></small>
                            </span>
                            <small class="nowrap"><?= e(fmt_datetime($a['created_at'])) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <section class="card">
        <div class="card-head"><h2>Precisa de atenção</h2></div>
        <ul class="list">
            <li>
                <a href="<?= e(url('/admin/anuncios/pendentes')) ?>">
                    <strong><?= (int) $t['pending_listings'] ?> anúncios aguardando aprovação</strong>
                    <small>Fila de moderação</small>
                </a>
            </li>
            <li>
                <a href="<?= e(url('/admin/criadores?verificado=pedido')) ?>">
                    <strong><?= (int) $t['verification_requests'] ?> criadores aguardando verificação</strong>
                    <small>Pedidos de selo</small>
                </a>
            </li>
            <li>
                <a href="<?= e(url('/admin/denuncias')) ?>">
                    <strong><?= (int) $t['open_reports'] ?> denúncias abertas</strong>
                    <small>Em análise ou novas</small>
                </a>
            </li>
            <li>
                <a href="<?= e(url('/admin/pagamentos?status=pending')) ?>">
                    <strong><?= (int) $t['pending_payments'] ?> pagamentos pendentes</strong>
                    <small>Aguardando confirmação</small>
                </a>
            </li>
            <li>
                <a href="<?= e(url('/admin/pagamentos?status=awaiting_payout')) ?>">
                    <strong><?= e(money($t['awaiting_payout'])) ?> a repassar</strong>
                    <small>Repasses para criadores</small>
                </a>
            </li>
        </ul>
        <?php if ($pendingListings !== []): ?>
            <div class="card-head"><h3>Próximos da fila</h3></div>
            <ul class="list">
                <?php foreach (array_slice($pendingListings, 0, 4) as $l): ?>
                    <li><a href="<?= e(url('/admin/anuncios/' . $l['uuid'])) ?>"><strong><?= e($l['title']) ?></strong><small><?= e($l['display_name']) ?> · <?= e(time_ago($l['submitted_at'])) ?></small></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($recentReports !== []): ?>
            <div class="card-head"><h3>Denúncias recentes</h3></div>
            <ul class="list">
                <?php foreach ($recentReports as $r): ?>
                    <li>
                        <a href="<?= e(url('/admin/denuncias/' . $r['uuid'])) ?>"><strong><?= e(ModerationController::REASONS[$r['reason']] ?? $r['reason']) ?></strong><small><?= e(time_ago($r['created_at'])) ?></small></a>
                        <?= status_badge('report', $r['status']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
