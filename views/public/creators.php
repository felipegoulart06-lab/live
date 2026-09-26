<?php $f = static fn (string $key): string => (string) ($filters[$key] ?? ''); ?>
<nav class="crumb" aria-label="Você está em"><a href="<?= e(url('/')) ?>">Início</a><span aria-hidden="true">/</span><span>Anúncios por perfil</span></nav>
<header class="page-head">
    <h1>Quem grava na plataforma</h1>
    <p class="lead">Perfis de serviço com anúncios de horas de vídeo. Nome completo, cidade, redes e telefone não são publicados.</p>
</header>

<form class="toolbar" method="get" action="<?= e(url('/criadores')) ?>" aria-label="Filtrar">
    <label class="field grow"><span>Buscar</span><input type="search" name="q" value="<?= e($f('q')) ?>" placeholder="Tipo de vídeo ou especialidade"></label>
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
    <?= view('empty', ['title' => 'Nenhum anúncio encontrado', 'message' => 'Tente outra busca ou veja o catálogo de anúncios.', 'action' => 'Ver anúncios', 'actionUrl' => url('/anuncios')]) ?>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($result['rows'] as $item): ?><?= view('creator-card', ['item' => $item]) ?><?php endforeach; ?>
    </div>
    <?= view('pager', ['result' => $result]) ?>
<?php endif; ?>
