<?php
use App\Queries\ListingQueries;

$f = static fn (string $key): string => (string) ($filters[$key] ?? '');
$activeFilters = array_filter($filters, static fn ($v, $k) => $v !== '' && $k !== 'ordem', ARRAY_FILTER_USE_BOTH);
?>
<nav class="crumb" aria-label="Você está em">
    <a href="<?= e(url('/')) ?>">Início</a><span aria-hidden="true">/</span>
    <?php if (!empty($filters['categoria'])): ?>
        <a href="<?= e(url('/anuncios')) ?>">Anúncios</a><span aria-hidden="true">/</span><span><?= e($heading) ?></span>
    <?php else: ?>
        <span>Anúncios</span>
    <?php endif; ?>
</nav>
<header class="page-head">
    <h1><?= e($heading) ?></h1>
    <p class="lead"><?= e($intro) ?></p>
</header>

<div class="listing-layout">
    <form class="filters-panel" method="get" action="<?= e(url('/anuncios')) ?>" aria-label="Filtros">
        <label class="field"><span>Buscar</span><input type="search" name="q" value="<?= e($f('q')) ?>" placeholder="Tema, criador…"></label>
        <label class="field"><span>Categoria</span>
            <select name="categoria">
                <option value="">Todas</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat['slug']) ?>" <?= $f('categoria') === $cat['slug'] ? 'selected' : '' ?>><?= e($cat['name']) ?> (<?= (int) $cat['listings_count'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="form-grid">
            <label class="field"><span>Preço mín.</span><input name="preco_min" inputmode="decimal" value="<?= e($f('preco_min')) ?>" placeholder="R$"></label>
            <label class="field"><span>Preço máx.</span><input name="preco_max" inputmode="decimal" value="<?= e($f('preco_max')) ?>" placeholder="R$"></label>
        </div>
        <label class="field"><span>Quantidade de horas</span>
            <select name="horas">
                <option value="">Qualquer</option>
                <?php foreach ($hourOptions as $h): ?>
                    <option value="<?= $h ?>" <?= $f('horas') === (string) $h ? 'selected' : '' ?>><?= $h ?>h</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field"><span>Entrega em até</span>
            <select name="prazo">
                <option value="">Qualquer prazo</option>
                <?php foreach ([3, 5, 7, 10, 15] as $d): ?>
                    <option value="<?= $d ?>" <?= $f('prazo') === (string) $d ? 'selected' : '' ?>><?= $d ?> dias</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field"><span>Avaliação mínima</span>
            <select name="nota">
                <option value="">Qualquer</option>
                <?php foreach ([4, 3] as $n): ?>
                    <option value="<?= $n ?>" <?= $f('nota') === (string) $n ? 'selected' : '' ?>><?= $n ?> estrelas ou mais</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field"><span>Cidade</span><input name="cidade" value="<?= e($f('cidade')) ?>" placeholder="Ex.: São Paulo"></label>
        <label class="check"><input type="checkbox" name="verificado" value="1" <?= $f('verificado') !== '' ? 'checked' : '' ?>> Só criadores verificados</label>
        <input type="hidden" name="ordem" value="<?= e($f('ordem')) ?>">
        <button class="btn btn-ink" type="submit">Aplicar filtros</button>
        <?php if ($activeFilters !== []): ?><a class="btn btn-ghost" href="<?= e(url('/anuncios')) ?>">Limpar filtros</a><?php endif; ?>
    </form>

    <div>
        <div class="results-bar">
            <p class="muted mb-0" role="status"><?= (int) $result['total'] ?> <?= (int) $result['total'] === 1 ? 'anúncio encontrado' : 'anúncios encontrados' ?></p>
            <form method="get" action="<?= e(url('/anuncios')) ?>" class="row">
                <?php foreach ($filters as $k => $v): if ($k === 'ordem' || $v === '') { continue; } ?>
                    <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
                <?php endforeach; ?>
                <label class="small" for="ordem">Ordenar por</label>
                <select id="ordem" name="ordem" class="input" style="width:auto" data-autosubmit>
                    <?php foreach (ListingQueries::SORTS as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= ($f('ordem') ?: 'relevancia') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <noscript><button class="btn btn-ghost btn-sm" type="submit">Ordenar</button></noscript>
            </form>
        </div>

        <?php if ($result['rows'] === []): ?>
            <?= view('empty', [
                'title' => 'Nenhum anúncio com esses filtros',
                'message' => 'Tente remover algum filtro ou buscar por outro termo.',
                'action' => 'Ver todos os anúncios',
                'actionUrl' => url('/anuncios'),
            ]) ?>
        <?php else: ?>
            <div class="card-grid card-grid--3">
                <?php foreach ($result['rows'] as $item): ?><?= view('listing-card', ['item' => $item]) ?><?php endforeach; ?>
            </div>
            <?= view('pager', ['result' => $result]) ?>
        <?php endif; ?>
    </div>
</div>
