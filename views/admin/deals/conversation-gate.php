<a class="back-link" href="<?= e(url('/admin/mensagens')) ?>">← Mensagens</a>
<div class="card card-pad" style="max-width:560px">
    <h1 style="font-size:1.6rem">Acesso à conversa</h1>
    <p class="muted">Conversa entre <strong><?= e($conversation['company_name']) ?></strong> e <strong><?= e($conversation['creator_name']) ?></strong><?= $conversation['listing_title'] ? ' sobre "' . e($conversation['listing_title']) . '"' : '' ?>.</p>
    <p class="alert alert-warn">As mensagens são privadas. Abra apenas para suporte, disputa ou investigação de denúncia. Seu nome, o horário e o motivo ficam registrados na auditoria.</p>
    <form class="form" method="get" action="<?= e(url('/admin/mensagens/' . $conversation['uuid'])) ?>">
        <label class="field"><span>Motivo do acesso</span><input name="motivo" required minlength="5" maxlength="300" placeholder="Ex.: Disputa no contrato CTR-..."></label>
        <button class="btn btn-ink" type="submit">Abrir conversa</button>
    </form>
</div>
