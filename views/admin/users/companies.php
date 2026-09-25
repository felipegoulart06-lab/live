<div class="page-title">
    <div><h1>Empresas</h1><p>Contas de empresa, contratos e valores investidos.</p></div>
</div>
<?= view('user-filters', ['action' => '/admin/empresas', 'creators' => false]) ?>
<?php if ($result['rows'] === []): ?>
    <?= view('empty', ['title' => 'Nenhuma empresa encontrada', 'action' => 'Limpar filtros', 'actionUrl' => url('/admin/empresas')]) ?>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Empresa</th><th scope="col">Responsável</th><th scope="col">Cidade</th><th scope="col" class="num">Contratos</th><th scope="col" class="num">Investido</th><th scope="col">Status</th><th scope="col">Cadastro</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $u): ?>
                <tr>
                    <td><a href="<?= e(url('/admin/empresas/' . $u['uuid'])) ?>"><strong><?= e($u['company_name']) ?></strong></a><small><?= e($u['email']) ?></small></td>
                    <td><?= e($u['display_name']) ?></td>
                    <td><?= e(trim(($u['city'] ?? '') . ($u['state'] ? '/' . $u['state'] : ''), '/') ?: '—') ?></td>
                    <td class="num"><?= (int) $u['contracts_count'] ?></td>
                    <td class="num"><?= e(money($u['invested'])) ?></td>
                    <td><?= status_badge('user', $u['status']) ?></td>
                    <td class="nowrap"><?= e(fmt_date($u['created_at'])) ?><small><?= is_online($u['last_seen_at']) ? 'Online agora' : 'Visto ' . e(time_ago($u['last_seen_at'])) ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= view('pager', ['result' => $result]) ?>
<?php endif; ?>
