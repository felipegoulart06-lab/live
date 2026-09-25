<?php $isMaster = $authUser->isMaster(); ?>
<div class="page-title">
    <div><h1>Administradores</h1><p>Moderadores (staff) cuidam de anúncios, usuários e denúncias. Só o nível master altera configurações, confirma pagamentos e gerencia administradores.</p></div>
</div>

<div class="grid-main">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Nome</th><th scope="col">Nível</th><th scope="col">Status</th><th scope="col">Último login</th><?php if ($isMaster): ?><th scope="col"><span class="sr-only">Ações</span></th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($admins as $a): ?>
                <tr>
                    <td><strong><?= e($a['display_name']) ?></strong><small><?= e($a['email']) ?></small></td>
                    <td><?= $a['admin_level'] === 'master' ? '<span class="status status--info">Master</span>' : '<span class="status status--neutral">Staff</span>' ?></td>
                    <td><?= status_badge('user', $a['status']) ?></td>
                    <td class="nowrap"><?= e(fmt_datetime($a['last_login_at'])) ?></td>
                    <?php if ($isMaster): ?>
                        <td>
                            <?php if ($a['uuid'] !== $authUser->uuid): ?>
                                <div class="actions-menu">
                                    <form method="post" action="<?= e(url('/admin/administradores/' . $a['uuid'] . '/nivel')) ?>" class="inline" data-confirm="Alterar o nível deste administrador?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="admin_level" value="<?= $a['admin_level'] === 'master' ? 'staff' : 'master' ?>">
                                        <button class="btn btn-ghost btn-sm" type="submit"><?= $a['admin_level'] === 'master' ? 'Tornar staff' : 'Tornar master' ?></button>
                                    </form>
                                    <?php if ($a['status'] === 'active'): ?>
                                        <details class="reveal">
                                            <summary>Bloquear</summary>
                                            <form class="reveal-body" method="post" action="<?= e(url('/admin/usuarios/' . $a['uuid'] . '/bloquear')) ?>">
                                                <?= csrf_field() ?>
                                                <label class="field"><span>Motivo</span><input name="reason" required minlength="5" maxlength="1000"></label>
                                                <button class="btn btn-danger btn-sm" type="submit">Bloquear</button>
                                            </form>
                                        </details>
                                    <?php else: ?>
                                        <form method="post" action="<?= e(url('/admin/usuarios/' . $a['uuid'] . '/desbloquear')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" type="submit">Desbloquear</button></form>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="muted small">Você</span>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($isMaster): ?>
        <form class="card card-pad form" method="post" action="<?= e(url('/admin/administradores')) ?>" novalidate>
            <?= csrf_field() ?>
            <h2 class="panel-title mb-0">Novo administrador</h2>
            <?= view('field', ['name' => 'display_name', 'label' => 'Nome', 'attrs' => 'required maxlength="80"']) ?>
            <?= view('field', ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'attrs' => 'required']) ?>
            <?= view('field', ['name' => 'password', 'label' => 'Senha inicial', 'type' => 'password', 'hint' => 'Mínimo de 8 caracteres, com letras e números. Peça para a pessoa trocar no primeiro acesso.', 'attrs' => 'required minlength="8" autocomplete="new-password"']) ?>
            <?= view('field', ['name' => 'admin_level', 'label' => 'Nível', 'type' => 'select', 'value' => 'staff', 'options' => ['staff' => 'Staff (moderação)', 'master' => 'Master (acesso total)']]) ?>
            <button class="btn btn-ink" type="submit">Criar administrador</button>
        </form>
    <?php else: ?>
        <p class="alert alert-info">Apenas administradores master podem criar ou alterar administradores.</p>
    <?php endif; ?>
</div>
