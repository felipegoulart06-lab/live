<?php
use App\Controllers\Admin\ModerationController;

$r = $report;
$targetUrl = $target ? ($r['target_type'] === 'listing' ? '/admin/anuncios/' . $target['uuid'] : '/admin/criadores/' . $target['uuid']) : null;
?>
<a class="back-link" href="<?= e(url('/admin/denuncias')) ?>">← Denúncias</a>
<div class="page-title">
    <div>
        <h1><?= e(ModerationController::REASONS[$r['reason']] ?? $r['reason']) ?></h1>
        <p class="row"><?= status_badge('report', $r['status']) ?> <span>Recebida em <?= e(fmt_datetime($r['created_at'])) ?></span></p>
    </div>
</div>
<div class="grid-main">
    <div class="stack">
        <section class="card card-pad">
            <h2 class="panel-title">Denúncia</h2>
            <dl class="kv">
                <dt>Alvo</dt>
                <dd>
                    <?php if ($target): ?>
                        <a href="<?= e(url($targetUrl)) ?>"><?= e($target['label']) ?></a> · <?= $r['target_type'] === 'listing' ? status_badge('listing', $target['status']) : status_badge('user', $target['status']) ?>
                    <?php else: ?>
                        <span class="muted">Item removido</span>
                    <?php endif; ?>
                </dd>
                <dt>Denunciante</dt>
                <dd>
                    <?php if ($reporter): ?>
                        <?php if (in_array($reporter['role'], ['creator', 'company'], true)): ?>
                            <a href="<?= e(url(($reporter['role'] === 'creator' ? '/admin/criadores/' : '/admin/empresas/') . $reporter['uuid'])) ?>"><?= e($reporter['display_name']) ?></a>
                        <?php else: ?>
                            <?= e($reporter['display_name']) ?>
                        <?php endif; ?>
                    <?php else: ?>—<?php endif; ?>
                </dd>
            </dl>
            <h3 style="margin-top:1rem">Descrição</h3>
            <div class="pre"><?= $r['description'] ? e($r['description']) : '<span class="muted">Sem descrição.</span>' ?></div>
            <?php if ($r['response']): ?>
                <h3 style="margin-top:1rem">Resposta enviada</h3>
                <div class="pre"><?= e($r['response']) ?></div>
            <?php endif; ?>
        </section>

        <section class="card card-pad">
            <h2 class="panel-title">Notas internas</h2>
            <?php if ($notes === []): ?>
                <p class="muted small mb-0">Nenhuma nota.</p>
            <?php else: ?>
                <ol class="timeline">
                    <?php foreach ($notes as $n): ?>
                        <li><strong><?= e($n['display_name'] ?? 'Equipe') ?></strong><small><?= e(fmt_datetime($n['created_at'])) ?></small><p class="pre"><?= e($n['note']) ?></p></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    </div>

    <form class="card card-pad form" method="post" action="<?= e(url('/admin/denuncias/' . $r['uuid'])) ?>">
        <?= csrf_field() ?>
        <h2 class="panel-title mb-0">Tratar</h2>
        <label class="field"><span>Situação</span>
            <select name="status">
                <?php foreach (status_map('report') as $k => [$label]): ?><option value="<?= e($k) ?>" <?= $r['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
            </select>
        </label>
        <label class="field"><span>Resposta ao denunciante</span><textarea name="response" rows="4" maxlength="1000"></textarea><small>Obrigatória ao resolver. É enviada como notificação.</small></label>
        <label class="field"><span>Nota interna</span><textarea name="note" rows="3" maxlength="1000"></textarea><small>Visível só para a equipe.</small></label>
        <button class="btn btn-ink" type="submit">Salvar</button>
        <?php if ($targetUrl): ?><p class="muted small mb-0">Para pausar o anúncio ou bloquear a conta, use a <a href="<?= e(url($targetUrl)) ?>">página do alvo</a>.</p><?php endif; ?>
    </form>
</div>
