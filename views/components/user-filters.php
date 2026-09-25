<?php
/** @var string $action @var bool $creators */
$g = static fn (string $k): string => is_string($_GET[$k] ?? null) ? (string) $_GET[$k] : '';
?>
<form class="toolbar" method="get" action="<?= e(url($action)) ?>">
    <label class="field grow"><span>Buscar</span><input type="search" name="q" value="<?= e($g('q')) ?>" placeholder="Nome ou e-mail<?= $creators ? '' : ' ou empresa' ?>"></label>
    <label class="field"><span>Status</span>
        <select name="status">
            <option value="">Todos</option>
            <?php foreach (status_map('user') as $k => [$label]): ?><option value="<?= e($k) ?>" <?= $g('status') === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
    </label>
    <?php if ($creators): ?>
        <label class="field"><span>Verificação</span>
            <select name="verificado">
                <option value="">Todos</option>
                <option value="1" <?= $g('verificado') === '1' ? 'selected' : '' ?>>Verificados</option>
                <option value="pedido" <?= $g('verificado') === 'pedido' ? 'selected' : '' ?>>Pedido em análise</option>
            </select>
        </label>
    <?php endif; ?>
    <label class="field"><span>Cadastro de</span><input type="date" name="de" value="<?= e($g('de')) ?>"></label>
    <label class="field"><span>até</span><input type="date" name="ate" value="<?= e($g('ate')) ?>"></label>
    <label class="field"><span>Ordenar</span>
        <select name="ordem">
            <?php foreach (['' => 'Mais recentes', 'antigos' => 'Mais antigos', 'nome' => 'Nome'] + ($creators ? ['avaliacao' => 'Avaliação'] : []) as $k => $label): ?>
                <option value="<?= e($k) ?>" <?= $g('ordem') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="btn btn-ink" type="submit">Filtrar</button>
</form>
