<?php $roles = ['creator' => 'Criador', 'company' => 'Empresa', 'admin' => 'Admin']; ?>
<div class="page-title"><div><h1>Atividade</h1><p>Registros permanentes. Não há edição nem exclusão pelo painel.</p></div></div>
<nav class="tabs" aria-label="Tipo de registro">
    <a href="<?= e(url('/admin/atividade')) ?>" class="<?= $tab === 'admin' ? 'is-active' : '' ?>">Ações da equipe</a>
    <a href="<?= e(url('/admin/atividade?aba=usuarios')) ?>" class="<?= $tab === 'usuarios' ? 'is-active' : '' ?>">Atividade dos usuários</a>
</nav>
<form class="toolbar" method="get" action="<?= e(url('/admin/atividade')) ?>">
    <?php if ($tab === 'usuarios'): ?><input type="hidden" name="aba" value="usuarios"><?php endif; ?>
    <label class="field grow"><span>Buscar</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="Descrição, ação ou pessoa"></label>
    <label class="field"><span>De</span><input type="date" name="de" value="<?= e((string) ($_GET['de'] ?? '')) ?>"></label>
    <label class="field"><span>Até</span><input type="date" name="ate" value="<?= e((string) ($_GET['ate'] ?? '')) ?>"></label>
    <button class="btn btn-ink" type="submit">Filtrar</button>
</form>
<?php if ($result['rows'] === []): ?>
    <?= view('empty', ['title' => 'Nenhum registro encontrado']) ?>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Quando</th><th scope="col">Quem</th><th scope="col">O que</th><th scope="col">Ação</th><th scope="col">IP</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $a): ?>
                <tr>
                    <td class="nowrap"><?= e(fmt_datetime($a['created_at'])) ?></td>
                    <td><?= e($a['display_name'] ?? 'Sistema') ?><?php if ($tab === 'usuarios' && !empty($a['role'])): ?><small><?= e($roles[$a['role']] ?? $a['role']) ?></small><?php endif; ?></td>
                    <td>
                        <?= e($a['description']) ?>
                        <?php if ($tab === 'admin' && !empty($a['meta'])): $meta = json_decode((string) $a['meta'], true); ?>
                            <?php if (is_array($meta) && $meta !== []): ?>
                                <small><?php foreach ($meta as $k => $v): ?><?= e($k) ?>: <?= e(is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v) ?>; <?php endforeach; ?></small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><code class="small"><?= e($a['action']) ?></code></td>
                    <td class="small"><?= e($a['ip'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= view('pager', ['result' => $result]) ?>
<?php endif; ?>
