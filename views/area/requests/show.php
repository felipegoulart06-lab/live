<?php
$isCreator = $area === '/painel';
$base = $area . '/solicitacoes/' . $req['uuid'];
?>
<a class="back-link" href="<?= e(url($area . '/solicitacoes')) ?>">← Solicitações</a>
<div class="page-title">
    <div>
        <h1>Solicitação <?= e($req['code']) ?></h1>
        <p class="row"><?= status_badge('request', $req['status']) ?> <span>Enviada em <?= e(fmt_datetime($req['created_at'])) ?></span></p>
    </div>
    <div class="row">
        <?php if ($req['conversation_uuid']): ?><a class="btn btn-ghost" href="<?= e(url($area . '/mensagens/' . $req['conversation_uuid'])) ?>">Mensagens</a><?php endif; ?>
        <?php if ($req['contract_uuid']): ?><a class="btn btn-ink" href="<?= e(url($area . '/contratos/' . $req['contract_uuid'])) ?>">Ver contrato <?= e($req['contract_code']) ?></a><?php endif; ?>
    </div>
</div>

<?php if ($req['status'] === 'declined' && $req['decline_reason']): ?>
    <div class="reason-box" style="margin-bottom:1rem"><strong>Motivo da recusa</strong><p class="mb-0 pre"><?= e($req['decline_reason']) ?></p></div>
<?php elseif ($req['status'] === 'expired'): ?>
    <div class="alert alert-warn">Esta solicitação expirou sem resposta do criador.</div>
<?php endif; ?>

<div class="grid-main">
    <div class="stack">
        <section class="card card-pad">
            <h2 class="panel-title">Pedido</h2>
            <dl class="kv">
                <dt>Anúncio</dt><dd><a href="<?= e(listing_url($req['listing_slug'])) ?>"><?= e($req['listing_title']) ?></a></dd>
                <dt><?= $isCreator ? 'Empresa' : 'Criador' ?></dt><dd><?= e($isCreator ? $req['company_name'] . ' (' . $req['company_contact'] . ')' : $req['creator_name']) ?></dd>
                <dt>Tema</dt><dd><?= e($req['theme']) ?></dd>
                <dt>Data desejada</dt><dd><?= $req['desired_date'] ? e(fmt_date($req['desired_date'])) . ($req['desired_time'] ? ' às ' . e(substr($req['desired_time'], 0, 5)) : '') : 'A combinar' ?></dd>
                <?php if ($req['status'] === 'pending'): ?><dt>Prazo de resposta</dt><dd><?= e(fmt_datetime($req['expires_at'])) ?></dd><?php endif; ?>
                <?php if ($req['responded_at']): ?><dt>Respondida em</dt><dd><?= e(fmt_datetime($req['responded_at'])) ?></dd><?php endif; ?>
            </dl>
        </section>
        <section class="card card-pad">
            <h2 class="panel-title">Briefing</h2>
            <?php if ($req['briefing']): ?><div class="pre"><?= e($req['briefing']) ?></div><?php else: ?><p class="muted mb-0">A empresa não enviou briefing.</p><?php endif; ?>
            <?php if ($req['message']): ?>
                <h3 style="margin-top:1rem">Mensagem</h3>
                <div class="pre"><?= e($req['message']) ?></div>
            <?php endif; ?>
        </section>
    </div>

    <div class="stack">
        <section class="card card-pad">
            <h2 class="panel-title">Valores</h2>
            <div class="money-lines">
                <div><span>Pacote de <?= (int) $req['hours'] ?>h</span><span><?= e(money($req['package_price_cents'])) ?></span></div>
                <?php foreach ($addons as $addon): ?>
                    <div><span><?= e($addon['name']) ?></span><span><?= e(money($addon['price_cents'])) ?></span></div>
                <?php endforeach; ?>
                <div class="total"><span>Total</span><span><?= e(money($req['total_cents'])) ?></span></div>
            </div>
            <?php if ($isCreator): ?>
                <p class="muted small" style="margin:.8rem 0 0">A plataforma retém <?= (int) setting('platform_fee_percent', 15) ?>%. O valor líquido aparece no contrato.</p>
            <?php endif; ?>
        </section>

        <?php if ($req['status'] === 'pending'): ?>
            <?php if ($isCreator): ?>
                <section class="card card-pad stack-sm">
                    <h2 class="panel-title mb-0">Responder</h2>
                    <form method="post" action="<?= e(url($base . '/aceitar')) ?>" data-confirm="Aceitar esta solicitação? O contrato será criado e a empresa poderá pagar.">
                        <?= csrf_field() ?>
                        <button class="btn btn-accent btn-block" type="submit">Aceitar e criar contrato</button>
                    </form>
                    <details class="reveal" <?= error_field('reason') ? 'open' : '' ?>>
                        <summary>Recusar</summary>
                        <form class="reveal-body" method="post" action="<?= e(url($base . '/recusar')) ?>">
                            <?= csrf_field() ?>
                            <?= view('field', ['name' => 'reason', 'label' => 'Motivo (a empresa vai ver)', 'type' => 'textarea', 'attrs' => 'required minlength="5" maxlength="500" rows="3"']) ?>
                            <button class="btn btn-danger" type="submit">Recusar solicitação</button>
                        </form>
                    </details>
                </section>
            <?php else: ?>
                <form class="card card-pad" method="post" action="<?= e(url($base . '/cancelar')) ?>" data-confirm="Cancelar esta solicitação?">
                    <?= csrf_field() ?>
                    <p class="muted small">Enquanto o criador não responde, você pode cancelar sem custo.</p>
                    <button class="btn btn-danger btn-block" type="submit">Cancelar solicitação</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
