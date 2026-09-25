<div class="page-title"><div><h1>Contratos</h1><p>Confirme pagamentos, acompanhe a execução e trate cancelamentos.</p></div></div>
<?= view('deal-filters', ['action' => '/admin/contratos', 'domain' => 'contract', 'placeholder' => 'Código, anúncio, empresa ou criador']) ?>
<?php if ($result['rows'] === []): ?>
    <?= view('empty', ['title' => 'Nenhum contrato encontrado']) ?>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Código</th><th scope="col">Empresa</th><th scope="col">Criador</th><th scope="col">Anúncio</th><th scope="col" class="num">Total</th><th scope="col" class="num">Taxa</th><th scope="col">Status</th><th scope="col">Criado</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $c): ?>
                <tr>
                    <td><a href="<?= e(url('/admin/contratos/' . $c['uuid'])) ?>"><strong><?= e($c['code']) ?></strong></a></td>
                    <td><?= e($c['company_name']) ?></td>
                    <td><?= e($c['creator_name']) ?></td>
                    <td><?= e($c['listing_title']) ?><small><?= (int) $c['hours'] ?>h<?= $c['scheduled_date'] ? ' · ' . e(fmt_date($c['scheduled_date'])) : '' ?></small></td>
                    <td class="num"><?= e(money($c['total_cents'])) ?></td>
                    <td class="num"><?= e(money($c['fee_cents'])) ?></td>
                    <td><?= status_badge('contract', $c['status']) ?></td>
                    <td class="nowrap"><?= e(fmt_date($c['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= view('pager', ['result' => $result]) ?>
<?php endif; ?>
