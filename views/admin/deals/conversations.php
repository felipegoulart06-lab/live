<div class="page-title"><div><h1>Mensagens</h1><p>Conversas entre empresas e criadores. Ler o conteúdo exige um motivo e fica registrado na auditoria.</p></div></div>
<form class="toolbar" method="get" action="<?= e(url('/admin/mensagens')) ?>">
    <label class="field grow"><span>Buscar</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="Empresa ou criador"></label>
    <button class="btn btn-ink" type="submit">Buscar</button>
</form>
<?php if ($result['rows'] === []): ?>
    <?= view('empty', ['title' => 'Nenhuma conversa encontrada']) ?>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Empresa</th><th scope="col">Criador</th><th scope="col">Anúncio</th><th scope="col" class="num">Mensagens</th><th scope="col">Última</th><th scope="col"><span class="sr-only">Ações</span></th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $cv): ?>
                <tr>
                    <td><?= e($cv['company_name']) ?></td>
                    <td><?= e($cv['creator_name']) ?></td>
                    <td><?= e($cv['listing_title'] ?? '—') ?></td>
                    <td class="num"><?= (int) $cv['messages_count'] ?></td>
                    <td class="nowrap"><?= e(fmt_datetime($cv['last_message_at'])) ?></td>
                    <td><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/mensagens/' . $cv['uuid'])) ?>">Abrir</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= view('pager', ['result' => $result]) ?>
<?php endif; ?>
