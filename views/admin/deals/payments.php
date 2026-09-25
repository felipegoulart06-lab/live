<?php $isMaster = $authUser->isMaster(); ?>
<div class="page-title"><div><h1>Pagamentos</h1><p>Recebimentos das empresas e repasses aos criadores. Os valores são registrados pela equipe; a estrutura já guarda gateway e referência para integração futura.</p></div></div>

<div class="stats stats--3">
    <?php foreach (['pending' => 'Pendentes', 'awaiting_payout' => 'Aguardando repasse', 'paid_out' => 'Repassados'] as $k => $label): $row = $totals[$k] ?? ['total' => 0, 'amount' => 0, 'net' => 0]; ?>
        <a class="stat<?= $k === 'awaiting_payout' ? ' stat--accent' : '' ?>" href="<?= e(url('/admin/pagamentos?status=' . $k)) ?>"><span><?= e($label) ?></span><strong><?= e(money($k === 'pending' ? $row['amount'] : $row['net'])) ?></strong><small><?= (int) $row['total'] ?> pagamento(s)</small></a>
    <?php endforeach; ?>
</div>

<?= view('deal-filters', ['action' => '/admin/pagamentos', 'domain' => 'payment', 'placeholder' => 'Contrato, empresa ou criador']) ?>
<?php if ($result['rows'] === []): ?>
    <?= view('empty', ['title' => 'Nenhum pagamento encontrado']) ?>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Contrato</th><th scope="col">Empresa → Criador</th><th scope="col" class="num">Valor</th><th scope="col" class="num">Taxa</th><th scope="col" class="num">Líquido</th><th scope="col">Status</th><th scope="col">Pago</th><th scope="col"><span class="sr-only">Ações</span></th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $p): ?>
                <tr>
                    <td><a href="<?= e(url('/admin/contratos/' . $p['contract_uuid'])) ?>"><strong><?= e($p['code']) ?></strong></a><?php if ($p['gateway_reference']): ?><small>Ref. <?= e($p['gateway_reference']) ?></small><?php endif; ?></td>
                    <td><?= e($p['company_name']) ?><small><?= e($p['creator_name']) ?></small></td>
                    <td class="num"><?= e(money($p['amount_cents'])) ?></td>
                    <td class="num"><?= e(money($p['fee_cents'])) ?></td>
                    <td class="num"><?= e(money($p['net_cents'])) ?></td>
                    <td><?= status_badge('payment', $p['status']) ?></td>
                    <td class="nowrap"><?= e(fmt_date($p['paid_at'])) ?><?php if ($p['paid_out_at']): ?><small>Repasse <?= e(fmt_date($p['paid_out_at'])) ?></small><?php endif; ?></td>
                    <td>
                        <?php if ($isMaster && $p['status'] === 'awaiting_payout'): ?>
                            <form method="post" action="<?= e(url('/admin/pagamentos/' . $p['uuid'] . '/repasse')) ?>" class="inline" data-confirm="Registrar o repasse de <?= e(money($p['net_cents'])) ?>?"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" type="submit">Registrar repasse</button></form>
                        <?php elseif ($p['status'] === 'pending'): ?>
                            <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/contratos/' . $p['contract_uuid'])) ?>">Conferir</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= view('pager', ['result' => $result]) ?>
<?php endif; ?>
