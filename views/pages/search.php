<section class="page-wrap">
    <header class="page-head">
        <p class="eyebrow">Busca</p>
        <h1><?= $query !== '' ? 'Resultados para “' . e($query) . '”' : 'Explorar serviços' ?></h1>
        <p class="muted"><?= (int) $total ?> serviços encontrados</p>
    </header>
    <form class="filters" method="get" action="<?= e(url('/buscar')) ?>">
        <input type="hidden" name="q" value="<?= e($query) ?>">
        <label>Categoria
            <select name="categoria">
                <option value="">Todas</option>
                <?php foreach ($menuCategories as $cat): ?>
                    <option value="<?= e($cat['slug']) ?>" <?= (($filters['categoria'] ?? '') === $cat['slug']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Preço mín.
            <input type="number" name="preco_min" step="0.01" min="0" value="<?= e($filters['preco_min'] ?? '') ?>">
        </label>
        <label>Preço máx.
            <input type="number" name="preco_max" step="0.01" min="0" value="<?= e($filters['preco_max'] ?? '') ?>">
        </label>
        <label class="check"><input type="checkbox" name="verificado" value="1" <?= (($filters['verificado'] ?? '') === '1') ? 'checked' : '' ?>> Verificado</label>
        <label class="check"><input type="checkbox" name="entrega_rapida" value="1" <?= (($filters['entrega_rapida'] ?? '') === '1') ? 'checked' : '' ?>> Entrega rápida</label>
        <label>Ordenar
            <select name="ordenar">
                <?php
                $opts = [
                    'relevantes' => 'Mais relevantes',
                    'vendidos' => 'Mais vendidos',
                    'avaliados' => 'Melhor avaliados',
                    'menor_preco' => 'Menor preço',
                    'maior_preco' => 'Maior preço',
                    'recentes' => 'Mais recentes',
                    'entrega' => 'Entrega mais rápida',
                ];
                foreach ($opts as $k => $label):
                ?>
                    <option value="<?= e($k) ?>" <?= (($filters['ordenar'] ?? 'relevantes') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn-ink" type="submit">Filtrar</button>
    </form>

    <?php if ($results): ?>
        <div class="card-grid">
            <?php foreach ($results as $service): ?>
                <?= \App\Core\View::component('service-card', ['service' => $service]) ?>
            <?php endforeach; ?>
        </div>
        <?php if ($pages > 1): ?>
            <nav class="pager">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a class="<?= $i === $page ? 'is-active' : '' ?>" href="<?= e(url('/buscar?' . http_build_query(array_merge($filters, ['q' => $query, 'page' => $i])))) ?>"><?= $i ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <?= \App\Core\View::component('empty', ['message' => 'Nenhum serviço corresponde a esses filtros.']) ?>
    <?php endif; ?>
</section>
