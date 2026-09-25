<?php $f = static fn (string $key): string => (string) ($filters[$key] ?? ''); ?>
<nav class="crumb" aria-label="Você está em"><a href="<?= e(url('/')) ?>">Início</a><span aria-hidden="true">/</span><span>Criadores</span></nav>
<header class="page-head">
    <h1>Criadores</h1>
    <p class="lead">Pessoas que vendem horas de vídeo com o próprio rosto, com anúncios revisados pela equipe.</p>
</header>

<form class="toolbar" method="get" action="<?= e(url('/criadores')) ?>" aria-label="Filtrar criadores">
    <label class="field grow"><span>Buscar</span><input type="search" name="q" value="<?= e($f('q')) ?>" placeholder="Nome, especialidade…"></label>
    <label class="field"><span>Cidade</span><input name="cidade" value="<?= e($f('cidade')) ?>"></label>
    <label class="field"><span>Ordenar</span>
        <select name="ordem">
            <?php foreach (['' => 'Relevância', 'avaliacao' => 'Melhor avaliados', 'contratados' => 'Mais contratados', 'novos' => 'Mais novos'] as $k => $label): ?>
                <option value="<?= e($k) ?>" <?= $f('ordem') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="check" style="align-self:center"><input type="checkbox" name="verificado" value="1" <?= $f('verificado') !== '' ? 'checked' : '' ?>> Verificados</label>
    <button class="btn btn-ink" type="submit">Filtrar</button>
</form>

<?php if ($result['rows'] === []): ?>
    <?= view('empty', ['title' => 'Nenhum criador encontrado', 'message' => 'Tente outra busca ou remova os filtros.', 'action' => 'Ver todos', 'actionUrl' => url('/criadores')]) ?>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($result['rows'] as $item): ?><?= view('creator-card', ['item' => $item]) ?><?php endforeach; ?>
    </div>
    <?= view('pager', ['result' => $result]) ?>
<?php endif; ?>
