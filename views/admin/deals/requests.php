<div class="page-title"><div><h1>Solicitações</h1><p>Pedidos de horas enviados por empresas, em todos os status.</p></div></div>
<?= view('deal-filters', ['action' => '/admin/solicitacoes', 'domain' => 'request', 'placeholder' => 'Código, anúncio, empresa ou criador']) ?>
<?php if ($result['rows'] === []): ?>
    <?= view('empty', ['title' => 'Nenhuma solicitação encontrada']) ?>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Código</th><th scope="col">Empresa</th><th scope="col">Criador</th><th scope="col">Anúncio</th><th scope="col" class="num">Total</th><th scope="col">Status</th><th scope="col">Enviada</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $r): ?>
                <tr>
                    <td><strong><?= e($r['code']) ?></strong><?php if ($r['contract_uuid']): ?><small><a href="<?= e(url('/admin/contratos/' . $r['contract_uuid'])) ?>">Ver contrato</a></small><?php endif; ?></td>
                    <td><?= e($r['company_name']) ?></td>
                    <td><?= e($r['creator_name']) ?></td>
                    <td><?= e($r['listing_title']) ?><small><?= (int) $r['hours'] ?>h</small></td>
                    <td class="num"><?= e(money($r['total_cents'])) ?></td>
                    <td><?= status_badge('request', $r['status']) ?><?php if ($r['status'] === 'pending'): ?><small>Expira <?= e(fmt_datetime($r['expires_at'])) ?></small><?php endif; ?></td>
                    <td class="nowrap"><?= e(fmt_datetime($r['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= view('pager', ['result' => $result]) ?>
<?php endif; ?>
