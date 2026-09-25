<?php $isCreator = $area === '/painel'; ?>
<div class="page-title">
    <div>
        <h1>Solicitações</h1>
        <p><?= $isCreator ? 'Pedidos de horas enviados pelas empresas. Aceite para gerar o contrato.' : 'Pedidos de horas que você enviou aos criadores.' ?></p>
    </div>
    <?php if (!$isCreator): ?><a class="btn btn-accent" href="<?= e(url('/anuncios')) ?>">Encontrar criadores</a><?php endif; ?>
</div>

<nav class="tabs" aria-label="Filtrar por status">
    <a href="<?= e(url($area . '/solicitacoes')) ?>" class="<?= $status === '' ? 'is-active' : '' ?>">Todas</a>
    <?php foreach (status_map('request') as $key => [$label]): ?>
        <a href="<?= e(url($area . '/solicitacoes?status=' . $key)) ?>" class="<?= $status === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>
<form class="toolbar" method="get" action="<?= e(url($area . '/solicitacoes')) ?>">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <label class="field grow"><span class="sr-only">Buscar</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="Código, anúncio ou tema"></label>
    <button class="btn btn-ghost" type="submit">Buscar</button>
</form>

<?php if ($result['rows'] === []): ?>
    <?= view('empty', $isCreator
        ? ['title' => 'Nenhuma solicitação por aqui', 'message' => 'Quando uma empresa pedir horas em um dos seus anúncios ativos, a solicitação aparece nesta lista.']
        : ['title' => 'Nenhuma solicitação por aqui', 'message' => 'Escolha um anúncio, selecione o pacote e envie o tema do vídeo.', 'action' => 'Ver anúncios', 'actionUrl' => url('/anuncios')]) ?>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Código</th><th scope="col"><?= $isCreator ? 'Empresa' : 'Criador' ?></th><th scope="col">Anúncio</th><th scope="col" class="num">Horas</th><th scope="col" class="num">Total</th><th scope="col">Status</th><th scope="col">Enviada</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $r): ?>
                <tr>
                    <td><a href="<?= e(url($area . '/solicitacoes/' . $r['uuid'])) ?>"><strong><?= e($r['code']) ?></strong></a></td>
                    <td><?= e($isCreator ? $r['company_name'] : $r['creator_name']) ?></td>
                    <td><?= e($r['listing_title']) ?><small><?= e($r['theme']) ?></small></td>
                    <td class="num"><?= (int) $r['hours'] ?>h</td>
                    <td class="num"><?= e(money($r['total_cents'])) ?></td>
                    <td><?= status_badge('request', $r['status']) ?><?php if ($r['status'] === 'pending'): ?><small>Expira <?= e(fmt_datetime($r['expires_at'])) ?></small><?php endif; ?></td>
                    <td class="nowrap"><?= e(fmt_date($r['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= view('pager', ['result' => $result]) ?>
<?php endif; ?>
