<div class="page-title"><div><h1>Avaliações</h1><p>Ocultar uma avaliação tira a nota da média do anúncio e do criador. O motivo fica registrado.</p></div></div>
<nav class="tabs" aria-label="Status">
    <a href="<?= e(url('/admin/avaliacoes')) ?>" class="<?= $status === '' ? 'is-active' : '' ?>">Todas</a>
    <?php foreach (status_map('review') as $k => [$label]): ?>
        <a href="<?= e(url('/admin/avaliacoes?status=' . $k)) ?>" class="<?= $status === $k ? 'is-active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <?php foreach ([1, 2, 3] as $n): ?>
        <a href="<?= e(url('/admin/avaliacoes?nota=' . $n)) ?>" class="<?= (string) ($_GET['nota'] ?? '') === (string) $n ? 'is-active' : '' ?>"><?= $n ?> ★</a>
    <?php endforeach; ?>
</nav>
<?php if ($result['rows'] === []): ?>
    <?= view('empty', ['title' => 'Nenhuma avaliação neste filtro']) ?>
<?php else: ?>
    <div class="stack">
        <?php foreach ($result['rows'] as $rv): ?>
            <article class="card card-pad">
                <div class="row-between">
                    <div>
                        <strong><?= e($rv['company_name']) ?></strong> avaliou <strong><?= e($rv['creator_name']) ?></strong>
                        <span class="stars" aria-label="<?= (int) $rv['rating'] ?> de 5"><?= rating_stars((float) $rv['rating']) ?></span>
                        <p class="muted small mb-0"><?= e($rv['listing_title']) ?> · <a href="<?= e(url('/admin/contratos/' . $rv['contract_uuid'])) ?>">contrato</a> · <?= e(fmt_date($rv['created_at'])) ?></p>
                    </div>
                    <?= status_badge('review', $rv['status']) ?>
                </div>
                <p class="pre" style="margin:.7rem 0"><?= e($rv['comment']) ?></p>
                <?php if ($rv['moderation_note']): ?><p class="small muted">Nota da moderação: <?= e($rv['moderation_note']) ?></p><?php endif; ?>
                <?php if ($rv['status'] === 'visible'): ?>
                    <details class="reveal">
                        <summary>Ocultar avaliação</summary>
                        <form class="reveal-body" method="post" action="<?= e(url('/admin/avaliacoes/' . (int) $rv['id'])) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="hidden">
                            <label class="field"><span>Motivo</span><input name="note" required minlength="5" maxlength="500"></label>
                            <button class="btn btn-danger btn-sm" type="submit">Ocultar</button>
                        </form>
                    </details>
                <?php else: ?>
                    <form method="post" action="<?= e(url('/admin/avaliacoes/' . (int) $rv['id'])) ?>"><?= csrf_field() ?><input type="hidden" name="status" value="visible"><button class="btn btn-ghost btn-sm" type="submit">Exibir novamente</button></form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <?= view('pager', ['result' => $result]) ?>
<?php endif; ?>
