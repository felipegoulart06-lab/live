<div class="page-title">
    <div>
        <h1>Financeiro</h1>
        <p>Valores líquidos, já descontada a taxa de <?= (int) setting('platform_fee_percent', 15) ?>% da plataforma. O repasse acontece depois que o contrato é concluído.</p>
    </div>
</div>

<div class="stats stats--5">
    <div class="stat"><span>Aguardando pagamento</span><strong><?= e(money($summary['pending'])) ?></strong><small>Empresa ainda não pagou</small></div>
    <div class="stat"><span>Pago, em execução</span><strong><?= e(money($summary['paid'])) ?></strong><small>Retido até a conclusão</small></div>
    <div class="stat stat--accent"><span>Aguardando repasse</span><strong><?= e(money($summary['awaiting_payout'])) ?></strong><small>Concluído, a receber</small></div>
    <div class="stat"><span>Repassado</span><strong><?= e(money($summary['paid_out'])) ?></strong><small>Já transferido para você</small></div>
    <div class="stat"><span>Taxas</span><strong><?= e(money($summary['fees'])) ?></strong><small>Retidas pela plataforma</small></div>
</div>

<nav class="tabs" aria-label="Filtrar por status">
    <a href="<?= e(url('/painel/financeiro')) ?>" class="<?= $status === '' ? 'is-active' : '' ?>">Todos</a>
    <?php foreach (status_map('payment') as $key => [$label]): ?>
        <a href="<?= e(url('/painel/financeiro?status=' . $key)) ?>" class="<?= $status === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>

<?php if ($result['rows'] === []): ?>
    <?= view('empty', ['title' => 'Nenhum pagamento neste filtro', 'message' => 'Os pagamentos aparecem quando uma solicitação vira contrato.']) ?>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Contrato</th><th scope="col">Empresa</th><th scope="col" class="num">Valor</th><th scope="col" class="num">Taxa</th><th scope="col" class="num">Você recebe</th><th scope="col">Status</th><th scope="col">Pago em</th><th scope="col">Repasse</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $pay): ?>
                <tr>
                    <td><a href="<?= e(url('/painel/contratos/' . $pay['contract_uuid'])) ?>"><strong><?= e($pay['code']) ?></strong></a></td>
                    <td><?= e($pay['company_name']) ?></td>
                    <td class="num"><?= e(money($pay['amount_cents'])) ?></td>
                    <td class="num">− <?= e(money($pay['fee_cents'])) ?></td>
                    <td class="num"><strong><?= e(money($pay['net_cents'])) ?></strong></td>
                    <td><?= status_badge('payment', $pay['status']) ?></td>
                    <td class="nowrap"><?= e(fmt_date($pay['paid_at'])) ?></td>
                    <td class="nowrap"><?= e(fmt_date($pay['paid_out_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= view('pager', ['result' => $result]) ?>
<?php endif; ?>
