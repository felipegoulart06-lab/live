<?php $isCreator = $area === '/painel'; ?>
<div class="page-title">
    <div>
        <h1>Contratos</h1>
        <p>Cada solicitação aceita vira um contrato, com pagamento, agenda e histórico.</p>
    </div>
</div>

<nav class="tabs" aria-label="Filtrar por status">
    <a href="<?= e(url($area . '/contratos')) ?>" class="<?= $status === '' ? 'is-active' : '' ?>">Todos</a>
    <?php foreach (status_map('contract') as $key => [$label]): ?>
        <a href="<?= e(url($area . '/contratos?status=' . $key)) ?>" class="<?= $status === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>
<form class="toolbar" method="get" action="<?= e(url($area . '/contratos')) ?>">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <label class="field grow"><span class="sr-only">Buscar</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="Código ou anúncio"></label>
    <button class="btn btn-ghost" type="submit">Buscar</button>
</form>

<?php if ($result['rows'] === []): ?>
    <?= view('empty', ['title' => 'Nenhum contrato encontrado', 'message' => $isCreator ? 'Os contratos aparecem quando você aceita uma solicitação.' : 'Os contratos aparecem quando o criador aceita a sua solicitação.']) ?>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Código</th><th scope="col"><?= $isCreator ? 'Empresa' : 'Criador' ?></th><th scope="col">Anúncio</th><th scope="col">Gravação</th><th scope="col" class="num"><?= $isCreator ? 'Você recebe' : 'Total' ?></th><th scope="col">Status</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $c): ?>
                <tr>
                    <td><a href="<?= e(url($area . '/contratos/' . $c['uuid'])) ?>"><strong><?= e($c['code']) ?></strong></a><small><?= e(fmt_date($c['created_at'])) ?></small></td>
                    <td><?= e($isCreator ? $c['company_name'] : $c['creator_name']) ?></td>
                    <td><?= e($c['listing_title']) ?><small><?= (int) $c['hours'] ?>h</small></td>
                    <td class="nowrap"><?= $c['scheduled_date'] ? e(fmt_date($c['scheduled_date'])) . ($c['scheduled_time'] ? ' ' . e(substr($c['scheduled_time'], 0, 5)) : '') : '<span class="muted">A combinar</span>' ?></td>
                    <td class="num"><?= e(money($isCreator ? $c['creator_amount_cents'] : $c['total_cents'])) ?></td>
                    <td><?= status_badge('contract', $c['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= view('pager', ['result' => $result]) ?>
<?php endif; ?>
