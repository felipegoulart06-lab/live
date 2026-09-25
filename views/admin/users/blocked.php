<div class="page-title">
    <div>
        <h1>Bloqueados</h1>
        <p>Contas impedidas de entrar na plataforma.</p>
    </div>
</div>

<form class="toolbar" method="get" action="<?= e(url('/admin/bloqueados')) ?>">
    <label class="field grow"><span>Buscar</span><input type="search" name="q" value="<?= e((string) ($_GET['q'] ?? '')) ?>" placeholder="Nome ou e-mail"></label>
    <label class="field"><span>Papel</span>
        <select name="papel">
            <option value="">Todos</option>
            <option value="creator" <?= ($_GET['papel'] ?? '') === 'creator' ? 'selected' : '' ?>>Criadores</option>
            <option value="company" <?= ($_GET['papel'] ?? '') === 'company' ? 'selected' : '' ?>>Empresas</option>
        </select>
    </label>
    <button class="btn btn-ink" type="submit">Filtrar</button>
</form>

<?php if ($result['rows'] === []): ?>
    <?= view('empty', ['title' => 'Nenhuma conta bloqueada', 'message' => 'Quando um usuário for bloqueado, ele aparece aqui.']) ?>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Usuário</th><th scope="col">Papel</th><th scope="col">Motivo</th><th scope="col">Cadastro</th><th scope="col"><span class="sr-only">Abrir</span></th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $u): ?>
                <?php $href = $u['role'] === 'creator' ? '/admin/criadores/' . $u['uuid'] : '/admin/empresas/' . $u['uuid']; ?>
                <tr>
                    <td>
                        <div class="person">
                            <?php if ($u['avatar_path']): ?><img class="avatar avatar-sm" src="<?= e(media($u['avatar_path'])) ?>" alt=""><?php else: ?><span class="avatar avatar-sm" aria-hidden="true"><?= e(initials($u['display_name'])) ?></span><?php endif; ?>
                            <div>
                                <a href="<?= e(url($href)) ?>"><strong><?= e($u['display_name']) ?></strong></a>
                                <small><?= e($u['email']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td><?= $u['role'] === 'creator' ? 'Criador' : 'Empresa' ?></td>
                    <td><?= e($u['blocked_reason'] ?: '—') ?></td>
                    <td class="nowrap"><?= e(fmt_date($u['created_at'])) ?></td>
                    <td><a class="btn btn-ghost btn-sm" href="<?= e(url($href)) ?>">Abrir</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= view('pager', ['result' => $result]) ?>
<?php endif; ?>
