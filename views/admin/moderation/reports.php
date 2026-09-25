<?php use App\Controllers\Admin\ModerationController; ?>
<div class="page-title"><div><h1>Denúncias</h1><p>Enviadas por usuários sobre anúncios e perfis. As abertas aparecem primeiro.</p></div></div>
<nav class="tabs" aria-label="Status">
    <a href="<?= e(url('/admin/denuncias')) ?>" class="<?= $status === '' ? 'is-active' : '' ?>">Todas</a>
    <?php foreach (status_map('report') as $k => [$label]): ?>
        <a href="<?= e(url('/admin/denuncias?status=' . $k)) ?>" class="<?= $status === $k ? 'is-active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>
<?php if ($result['rows'] === []): ?>
    <?= view('empty', ['title' => 'Nenhuma denúncia neste filtro']) ?>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Motivo</th><th scope="col">Alvo</th><th scope="col">Denunciante</th><th scope="col">Status</th><th scope="col">Recebida</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $r): ?>
                <tr>
                    <td><a href="<?= e(url('/admin/denuncias/' . $r['uuid'])) ?>"><strong><?= e(ModerationController::REASONS[$r['reason']] ?? $r['reason']) ?></strong></a></td>
                    <td><?= e($r['target_label'] ?? 'Removido') ?><small><?= $r['target_type'] === 'listing' ? 'Anúncio' : 'Criador' ?></small></td>
                    <td><?= e($r['reporter_name'] ?? '—') ?></td>
                    <td><?= status_badge('report', $r['status']) ?></td>
                    <td class="nowrap"><?= e(fmt_datetime($r['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= view('pager', ['result' => $result]) ?>
<?php endif; ?>
