<?php
$l = $listing;
$base = '/admin/anuncios/' . $l['uuid'];
$labels = ['submitted' => 'Enviado para revisão', 'approved' => 'Aprovado', 'rejected' => 'Reprovado', 'paused' => 'Pausado', 'reactivated' => 'Reativado', 'deleted' => 'Excluído'];
$val = static fn (string $k): string => (string) old($k, $l[$k] ?? '');
?>
<a class="back-link" href="<?= e(url($l['status'] === 'pending' ? '/admin/anuncios/pendentes' : '/admin/anuncios')) ?>">← Anúncios</a>
<div class="page-title">
    <div>
        <h1><?= e($l['title']) ?></h1>
        <p class="row"><?= status_badge('listing', $l['status']) ?> <span>de <a href="<?= e(url('/admin/criadores/' . $creatorUuid)) ?>"><?= e($l['display_name']) ?></a></span> <span class="muted">· <?= e($l['category_name'] ?? 'Sem categoria') ?></span></p>
    </div>
    <div class="row">
        <a class="btn btn-ghost" href="<?= e(url($base . '/previa')) ?>">Ver como a empresa vê</a>
        <?php if ($l['status'] === 'active'): ?><a class="btn btn-ghost" href="<?= e(listing_url($l['slug'])) ?>">Abrir no site</a><?php endif; ?>
    </div>
</div>

<div class="grid-main">
    <div class="stack">
        <?php if ($images !== []): ?>
            <section class="card card-pad">
                <h2 class="panel-title">Fotos (<?= count($images) ?>)</h2>
                <div class="image-grid">
                    <?php foreach ($images as $img): ?>
                        <a class="image-tile" href="<?= e(media($img['path'])) ?>" target="_blank" rel="noopener"><img src="<?= e(media($img['thumb_path'] ?: $img['path'])) ?>" alt="<?= e($img['alt_text'] ?? '') ?>"><?php if ((int) $img['is_primary'] === 1): ?><span class="badge" style="margin:.4rem">Capa</span><?php endif; ?></a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="card card-pad">
            <h2 class="panel-title">Conteúdo</h2>
            <p><strong>Resumo:</strong> <?= e($l['short_description']) ?></p>
            <div class="prose"><?= nl2p($l['description']) ?></div>
            <div class="info-list">
                <?php foreach (['what_company_buys' => 'O que a empresa contrata', 'what_company_provides' => 'O que a empresa envia', 'how_it_works' => 'Como funciona', 'framing' => 'Enquadramento', 'additional_info' => 'Informações adicionais'] as $f => $label): ?>
                    <?php if (!empty($l[$f])): ?><div><h3><?= e($label) ?></h3><p><?= e($l[$f]) ?></p></div><?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php if ($embed): ?>
                <h3 style="margin-top:1rem">Vídeo</h3>
                <div class="video-embed"><iframe src="<?= e($embed) ?>" title="Vídeo do anúncio" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>
            <?php endif; ?>
        </section>

        <section class="card card-pad">
            <h2 class="panel-title">Pacotes e adicionais</h2>
            <?php if ($packages === []): ?>
                <p class="muted">Nenhum pacote cadastrado.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th scope="col">Horas</th><th scope="col" class="num">Preço</th><th scope="col">Entrega</th><th scope="col">Revisões</th><th scope="col">Inclui</th></tr></thead>
                        <tbody><?php foreach ($packages as $p): ?><tr><td><?= (int) $p['hours'] ?>h</td><td class="num"><?= e(money($p['price_cents'])) ?></td><td><?= (int) $p['delivery_days'] ?> dias</td><td><?= (int) $p['revisions'] ?></td><td><?= e($p['description'] ?? '') ?></td></tr><?php endforeach; ?></tbody>
                    </table>
                </div>
            <?php endif; ?>
            <?php if ($addons !== []): ?>
                <ul class="list" style="margin-top:1rem">
                    <?php foreach ($addons as $a): ?><li><span><strong><?= e($a['name']) ?></strong><small><?= e($a['description'] ?? '') ?></small></span><span class="nowrap"><?= e(money($a['price_cents'])) ?><?= (int) $a['extra_days'] ? ' · +' . (int) $a['extra_days'] . 'd' : '' ?></span></li><?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <details class="reveal" <?= error_field('title') || error_field('description') || error_field('short_description') ? 'open' : '' ?>>
            <summary>Corrigir texto do anúncio</summary>
            <form class="reveal-body" method="post" action="<?= e(url($base)) ?>">
                <?= csrf_field() ?>
                <p class="muted small mb-0">Use para pequenas correções (erros de digitação, dados de contato no texto). A alteração fica registrada na auditoria.</p>
                <?= view('field', ['name' => 'category_id', 'label' => 'Categoria', 'type' => 'select', 'value' => $val('category_id'), 'options' => array_column($categories, 'name', 'id')]) ?>
                <?= view('field', ['name' => 'title', 'label' => 'Título', 'value' => $val('title'), 'attrs' => 'required minlength="10" maxlength="90"']) ?>
                <?= view('field', ['name' => 'short_description', 'label' => 'Resumo', 'type' => 'textarea', 'value' => $val('short_description'), 'attrs' => 'required rows="2" maxlength="200"']) ?>
                <?= view('field', ['name' => 'description', 'label' => 'Descrição', 'type' => 'textarea', 'value' => $val('description'), 'attrs' => 'required rows="6" maxlength="5000"']) ?>
                <div><button class="btn btn-ink" type="submit">Salvar correção</button></div>
            </form>
        </details>
    </div>

    <div class="stack">
        <section class="card card-pad" id="moderar">
            <h2 class="panel-title">Moderação</h2>
            <ul class="checklist" style="margin-bottom:1rem">
                <?php foreach ($checklist as $item): ?>
                    <li class="<?= $item['ok'] ? 'done' : '' ?>"><span class="tick" aria-hidden="true"><?= $item['ok'] ? '✓' : '!' ?></span><span><?= e($item['label']) ?><?= $item['ok'] ? '' : ' <strong>(faltando)</strong>' ?></span></li>
                <?php endforeach; ?>
            </ul>
            <?php if ($l['status'] === 'pending'): ?>
                <form method="post" action="<?= e(url($base . '/aprovar')) ?>" style="margin-bottom:.8rem"><?= csrf_field() ?><button class="btn btn-accent btn-block" type="submit">Aprovar e publicar</button></form>
            <?php endif; ?>
            <?php if (in_array($l['status'], ['pending', 'active', 'paused'], true)): ?>
                <details class="reveal" <?= error_field('reason') ? 'open' : '' ?>>
                    <summary>Reprovar</summary>
                    <form class="reveal-body" method="post" action="<?= e(url($base . '/reprovar')) ?>">
                        <?= csrf_field() ?>
                        <?= view('field', ['name' => 'reason', 'label' => 'Motivo (o criador vai ler)', 'type' => 'textarea', 'hint' => 'Diga o que precisa mudar. Mínimo de 10 caracteres.', 'attrs' => 'required minlength="10" maxlength="1000" rows="4"']) ?>
                        <button class="btn btn-danger" type="submit">Reprovar anúncio</button>
                    </form>
                </details>
            <?php endif; ?>
            <?php if ($l['status'] === 'active'): ?>
                <details class="reveal" style="margin-top:.6rem">
                    <summary>Pausar</summary>
                    <form class="reveal-body" method="post" action="<?= e(url($base . '/pausar')) ?>">
                        <?= csrf_field() ?>
                        <label class="field"><span>Motivo</span><textarea name="reason" required minlength="5" maxlength="1000" rows="3"></textarea></label>
                        <button class="btn btn-ghost" type="submit">Pausar anúncio</button>
                    </form>
                </details>
            <?php elseif ($l['status'] === 'paused'): ?>
                <form method="post" action="<?= e(url($base . '/reativar')) ?>" style="margin-top:.6rem"><?= csrf_field() ?><button class="btn btn-ghost btn-block" type="submit">Reativar anúncio</button></form>
            <?php endif; ?>
            <?php if (in_array($l['status'], ['draft', 'rejected'], true)): ?>
                <p class="muted small mb-0">O criador precisa enviar o anúncio para revisão antes da moderação.</p>
            <?php endif; ?>
            <form method="post" action="<?= e(url($base . '/excluir')) ?>" style="margin-top:1rem" data-confirm="Excluir este anúncio? Ele sai do site e das buscas."><?= csrf_field() ?><button class="btn btn-danger btn-sm" type="submit">Excluir anúncio</button></form>
        </section>

        <?php if ($l['status'] === 'rejected' && $l['rejection_reason']): ?>
            <div class="reason-box"><strong>Motivo da reprovação atual</strong><p class="mb-0 pre"><?= e($l['rejection_reason']) ?></p></div>
        <?php endif; ?>

        <section class="card card-pad">
            <h2 class="panel-title">Dados</h2>
            <dl class="kv">
                <dt>Criado</dt><dd><?= e(fmt_datetime($l['created_at'])) ?></dd>
                <dt>Enviado</dt><dd><?= e(fmt_datetime($l['submitted_at'])) ?></dd>
                <dt>Publicado</dt><dd><?= e(fmt_datetime($l['published_at'])) ?></dd>
                <dt>Visitas</dt><dd><?= (int) $l['views_count'] ?></dd>
                <dt>Solicitações</dt><dd><?= (int) $l['contacts_count'] ?></dd>
                <dt>Contratos</dt><dd><?= (int) $l['contracts_count'] ?></dd>
                <dt>Avaliação</dt><dd><?= (int) $l['rating_count'] > 0 ? number_format((float) $l['rating_avg'], 1, ',', '') . ' (' . (int) $l['rating_count'] . ')' : '—' ?></dd>
            </dl>
        </section>

        <section class="card card-pad">
            <h2 class="panel-title">Histórico de moderação</h2>
            <?php if ($history === []): ?>
                <p class="muted small mb-0">Sem registros.</p>
            <?php else: ?>
                <ol class="timeline">
                    <?php foreach ($history as $h): ?>
                        <li>
                            <strong><?= e($labels[$h['action']] ?? $h['action']) ?></strong>
                            <small><?= e(fmt_datetime($h['created_at'])) ?> · <?= e($h['actor_name'] ?? 'Sistema') ?><?= $h['actor_role'] === 'admin' ? ' (equipe)' : '' ?></small>
                            <?php if ($h['reason']): ?><p class="pre"><?= e($h['reason']) ?></p><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    </div>
</div>
