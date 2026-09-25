<?php $base = $onlyPending ? '/admin/anuncios/pendentes' : '/admin/anuncios'; ?>
<div class="page-title">
    <div>
        <h1><?= $onlyPending ? 'Anúncios pendentes' : 'Anúncios' ?></h1>
        <p><?= $onlyPending ? 'Fila de revisão, do envio mais antigo para o mais novo.' : 'Todos os anúncios da plataforma, em qualquer status.' ?></p>
    </div>
</div>

<?php if (!$onlyPending): ?>
    <nav class="tabs" aria-label="Status">
        <a href="<?= e(url('/admin/anuncios')) ?>" class="<?= $status === '' ? 'is-active' : '' ?>">Todos</a>
        <?php foreach (status_map('listing') as $key => [$label]): ?>
            <a href="<?= e(url('/admin/anuncios?status=' . $key)) ?>" class="<?= $status === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

<form class="toolbar" method="get" action="<?= e(url($base)) ?>">
    <?php if (!$onlyPending && $status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <label class="field grow"><span>Buscar</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="Título ou criador"></label>
    <label class="field"><span>Categoria</span>
        <select name="categoria">
            <option value="">Todas</option>
            <?php foreach ($categories as $cat): ?><option value="<?= (int) $cat['id'] ?>" <?= $category === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option><?php endforeach; ?>
        </select>
    </label>
    <label class="field"><span>Criado de</span><input type="date" name="de" value="<?= e((string) ($_GET['de'] ?? '')) ?>"></label>
    <label class="field"><span>até</span><input type="date" name="ate" value="<?= e((string) ($_GET['ate'] ?? '')) ?>"></label>
    <label class="field"><span>Ordenar</span>
        <select name="ordem">
            <?php foreach (['' => 'Padrão', 'antigos' => 'Mais antigos', 'contratos' => 'Mais contratos', 'visitas' => 'Mais visitas'] as $k => $label): ?>
                <option value="<?= e($k) ?>" <?= (string) ($_GET['ordem'] ?? '') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="btn btn-ink" type="submit">Filtrar</button>
</form>

<?php if ($result['rows'] === []): ?>
    <?= view('empty', $onlyPending ? ['title' => 'Fila vazia', 'message' => 'Nenhum anúncio aguardando revisão.'] : ['title' => 'Nenhum anúncio encontrado', 'action' => 'Limpar filtros', 'actionUrl' => url('/admin/anuncios')]) ?>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Anúncio</th><th scope="col">Criador</th><th scope="col">Status</th><th scope="col" class="num">Preço</th><th scope="col" class="num">Visitas</th><th scope="col" class="num">Contratos</th><th scope="col"><?= $onlyPending ? 'Enviado' : 'Atualizado' ?></th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $l): ?>
                <tr>
                    <td>
                        <div class="person">
                            <?php if ($l['cover_path']): ?><img class="thumb-sm" src="<?= e(media($l['cover_path'])) ?>" alt=""><?php else: ?><span class="thumb-sm" aria-hidden="true"></span><?php endif; ?>
                            <div><a href="<?= e(url('/admin/anuncios/' . $l['uuid'])) ?>"><strong><?= e($l['title']) ?></strong></a><small><?= e($l['category_name'] ?? 'Sem categoria') ?></small></div>
                        </div>
                    </td>
                    <td><?= e($l['display_name']) ?></td>
                    <td><?= status_badge('listing', $l['status']) ?></td>
                    <td class="num"><?= (int) $l['starting_price_cents'] > 0 ? e(money($l['starting_price_cents'])) : '—' ?></td>
                    <td class="num"><?= (int) $l['views_count'] ?></td>
                    <td class="num"><?= (int) $l['contracts_count'] ?></td>
                    <td class="nowrap"><?= e(fmt_datetime($onlyPending ? $l['submitted_at'] : $l['updated_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= view('pager', ['result' => $result]) ?>
<?php endif; ?>
