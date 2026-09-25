<?php $total = array_sum($counts); ?>
<div class="page-title">
    <div>
        <h1>Meus anúncios</h1>
        <p>Rascunhos, anúncios em revisão e publicados.</p>
    </div>
    <a class="btn btn-accent" href="<?= e(url('/painel/anuncios/novo')) ?>">Novo anúncio</a>
</div>

<nav class="tabs" aria-label="Filtrar por status">
    <a href="<?= e(url('/painel/anuncios')) ?>" class="<?= $status === '' ? 'is-active' : '' ?>">Todos <small><?= $total ?></small></a>
    <?php foreach (status_map('listing') as $key => [$label]): ?>
        <a href="<?= e(url('/painel/anuncios?status=' . $key)) ?>" class="<?= $status === $key ? 'is-active' : '' ?>"><?= e($label) ?> <small><?= (int) ($counts[$key] ?? 0) ?></small></a>
    <?php endforeach; ?>
</nav>

<form class="toolbar" method="get" action="<?= e(url('/painel/anuncios')) ?>">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <label class="field grow"><span class="sr-only">Buscar por título</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por título"></label>
    <button class="btn btn-ghost" type="submit">Buscar</button>
</form>

<?php if ($result['rows'] === []): ?>
    <?php if ($total === 0): ?>
        <?= view('empty', ['title' => 'Você ainda não tem anúncios', 'message' => 'Crie o primeiro anúncio com seus pacotes de horas. Ele passa por uma revisão rápida antes de aparecer no site.', 'action' => 'Criar anúncio', 'actionUrl' => url('/painel/anuncios/novo')]) ?>
    <?php else: ?>
        <?= view('empty', ['title' => 'Nenhum anúncio neste filtro', 'action' => 'Ver todos', 'actionUrl' => url('/painel/anuncios')]) ?>
    <?php endif; ?>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Anúncio</th><th scope="col">Status</th><th scope="col" class="num">A partir de</th><th scope="col" class="num">Visitas</th><th scope="col" class="num">Contratos</th><th scope="col">Atualizado</th><th scope="col"><span class="sr-only">Ações</span></th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $l): ?>
                <tr>
                    <td>
                        <div class="person">
                            <?php if ($l['cover_path']): ?><img class="thumb-sm" src="<?= e(media($l['cover_path'])) ?>" alt=""><?php else: ?><span class="thumb-sm" aria-hidden="true"></span><?php endif; ?>
                            <div>
                                <a href="<?= e(url('/painel/anuncios/' . $l['uuid'])) ?>"><strong><?= e($l['title']) ?></strong></a>
                                <small><?= e($l['category_name'] ?? 'Sem categoria') ?></small>
                                <?php if ($l['status'] === 'rejected' && $l['rejection_reason']): ?><small style="color:var(--err)">Motivo: <?= e($l['rejection_reason']) ?></small><?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><?= status_badge('listing', $l['status']) ?></td>
                    <td class="num"><?= (int) $l['starting_price_cents'] > 0 ? e(money($l['starting_price_cents'])) : '—' ?></td>
                    <td class="num"><?= (int) $l['views_count'] ?></td>
                    <td class="num"><?= (int) $l['contracts_count'] ?></td>
                    <td class="nowrap"><?= e(fmt_date($l['updated_at'])) ?></td>
                    <td>
                        <div class="actions-menu">
                            <a class="btn btn-ghost btn-sm" href="<?= e(url('/painel/anuncios/' . $l['uuid'])) ?>">Editar</a>
                            <?php if ($l['status'] === 'active'): ?>
                                <a class="btn btn-ghost btn-sm" href="<?= e(listing_url($l['slug'])) ?>">Ver</a>
                                <form method="post" action="<?= e(url('/painel/anuncios/' . $l['uuid'] . '/pausar')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" type="submit">Pausar</button></form>
                            <?php elseif ($l['status'] === 'paused'): ?>
                                <form method="post" action="<?= e(url('/painel/anuncios/' . $l['uuid'] . '/reativar')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" type="submit">Reativar</button></form>
                            <?php endif; ?>
                            <form method="post" action="<?= e(url('/painel/anuncios/' . $l['uuid'] . '/duplicar')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" type="submit">Duplicar</button></form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= view('pager', ['result' => $result]) ?>
<?php endif; ?>
