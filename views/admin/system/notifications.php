<?php $canSend = $authUser->isMaster(); ?>
<div class="page-title"><div><h1>Notificações</h1><p>Avisos gerais enviados para o painel de criadores e empresas. Para as suas notificações, use o sino no topo.</p></div></div>
<div class="grid-main">
    <?php if ($canSend): ?>
        <form class="card card-pad form" method="post" action="<?= e(url('/admin/notificacoes')) ?>" novalidate data-confirm="Enviar esta notificação para todos os usuários do público escolhido?">
            <?= csrf_field() ?>
            <h2 class="panel-title mb-0">Enviar aviso</h2>
            <?= view('field', ['name' => 'audience', 'label' => 'Público', 'type' => 'select', 'value' => 'all', 'options' => ['all' => 'Criadores e empresas', 'creator' => 'Só criadores', 'company' => 'Só empresas']]) ?>
            <?= view('field', ['name' => 'title', 'label' => 'Título', 'attrs' => 'required minlength="5" maxlength="120"']) ?>
            <?= view('field', ['name' => 'body', 'label' => 'Mensagem', 'type' => 'textarea', 'attrs' => 'required minlength="10" maxlength="500" rows="4" data-count="500"']) ?>
            <div class="form-actions"><button class="btn btn-ink" type="submit">Enviar</button></div>
        </form>
    <?php else: ?>
        <div class="alert alert-info">Apenas administradores master enviam avisos gerais.</div>
    <?php endif; ?>
    <section class="card">
        <div class="card-head"><h2>Enviados</h2></div>
        <?php if ($recent === []): ?>
            <p class="list-empty">Nenhum aviso geral enviado.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($recent as $r): ?>
                    <li><span><strong><?= e($r['description']) ?></strong></span><small class="nowrap"><?= e(fmt_datetime($r['created_at'])) ?></small></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
