<?php
use App\Services\Listings;

/** @var array<string, mixed>|null $listing */
$isNew = $listing === null;
$base = $isNew ? '' : '/painel/anuncios/' . $listing['uuid'];
$action = static fn (int $n): string => url($base . '/etapa/' . $n);
$val = static fn (string $field): string => (string) old($field, $listing[$field] ?? '');
$maxStep = $isNew ? 1 : max(1, (int) $listing['wizard_step']);
$live = !$isNew && in_array($listing['status'], ['active', 'paused'], true) && setting('require_listing_approval', '1') === '1';
$oldRows = old('rows', null);
$packageRows = is_array($oldRows) && $step === 3 ? $oldRows : array_map(static fn (array $p): array => [
    'id' => (string) $p['id'], 'hours' => (string) $p['hours'], 'price' => money_input($p['price_cents']),
    'delivery_days' => (string) $p['delivery_days'], 'revisions' => (string) $p['revisions'], 'description' => (string) ($p['description'] ?? ''),
], $packages ?? []);
$addonRows = is_array($oldRows) && $step === 4 ? $oldRows : array_map(static fn (array $a): array => [
    'id' => (string) $a['id'], 'name' => (string) $a['name'], 'description' => (string) ($a['description'] ?? ''),
    'price' => money_input($a['price_cents']), 'extra_days' => (string) $a['extra_days'],
], $addons ?? []);
$faqRows = $faqs ?? [];
?>
<a class="back-link" href="<?= e(url('/painel/anuncios')) ?>">← Meus anúncios</a>
<div class="page-title">
    <div>
        <h1><?= $isNew ? 'Novo anúncio' : e($listing['title']) ?></h1>
        <?php if (!$isNew): ?><p class="row"><?= status_badge('listing', $listing['status']) ?> <span>Atualizado <?= e(time_ago($listing['updated_at'])) ?></span></p><?php endif; ?>
    </div>
    <?php if (!$isNew): ?>
        <div class="row">
            <a class="btn btn-ghost" href="<?= e(url($base . '/previa')) ?>">Pré-visualizar</a>
            <?php if ($listing['status'] === 'active'): ?><a class="btn btn-ghost" href="<?= e(listing_url($listing['slug'])) ?>">Ver no site</a><?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<ol class="steps" aria-label="Etapas do anúncio">
    <?php foreach (Listings::STEPS as $n => $label): ?>
        <?php $state = $n === $step ? 'is-current' : ($n < $maxStep ? 'is-done' : ''); ?>
        <li class="<?= $state ?>">
            <?php if (!$isNew && $n <= $maxStep): ?>
                <a href="<?= e(url($base . '?etapa=' . $n)) ?>" <?= $n === $step ? 'aria-current="step"' : '' ?>><span><?= $n ?>.</span><b><?= e($label) ?></b></a>
            <?php else: ?>
                <span <?= $n === $step ? 'aria-current="step"' : '' ?>><span><?= $n ?>.</span><b><?= e($label) ?></b></span>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ol>

<?php if (!$isNew && $listing['status'] === 'rejected' && $listing['rejection_reason']): ?>
    <div class="reason-box" style="margin-bottom:1rem" role="alert">
        <strong>A moderação pediu ajustes:</strong>
        <p class="mb-0 pre"><?= e($listing['rejection_reason']) ?></p>
        <p class="small mb-0">Corrija os pontos acima e reenvie na etapa Publicação.</p>
    </div>
<?php endif; ?>
<?php if ($live && in_array($step, [1, 2], true)): ?>
    <div class="alert alert-warn">Este anúncio está <?= $listing['status'] === 'active' ? 'publicado' : 'pausado' ?>. Alterar o texto ou as fotos devolve o anúncio para revisão até a moderação aprovar de novo. Preços e adicionais podem ser alterados sem nova revisão.</div>
<?php endif; ?>

<?php if ($step === 1): ?>
    <form class="card card-pad form" method="post" action="<?= e($isNew ? url('/painel/anuncios/novo') : $action(1)) ?>" novalidate>
        <?= csrf_field() ?>
        <h2 class="panel-title mb-0">Qual serviço você vende?</h2>
        <?= view('field', ['name' => 'category_id', 'label' => 'Categoria', 'type' => 'select', 'value' => $val('category_id'),
            'options' => ['' => 'Escolha uma categoria'] + array_column($categories, 'name', 'id'), 'attrs' => 'required']) ?>
        <?= view('field', ['name' => 'title', 'label' => 'Título do anúncio', 'value' => $val('title'), 'hint' => 'De 10 a 90 caracteres. Diga o tipo de vídeo e o formato. Ex.: "Vídeo institucional com apresentadora em estúdio".', 'attrs' => 'required minlength="10" maxlength="90" data-count="90"']) ?>
        <?= view('field', ['name' => 'short_description', 'label' => 'Resumo', 'type' => 'textarea', 'value' => $val('short_description'), 'hint' => 'Aparece nos cards da busca. De 20 a 200 caracteres.', 'attrs' => 'required rows="3" minlength="20" maxlength="200" data-count="200"']) ?>
        <div class="form-actions">
            <button class="btn btn-ink" type="submit"><?= $isNew ? 'Criar rascunho e continuar' : 'Salvar e continuar' ?></button>
            <?php if (!$isNew): ?><button class="btn btn-ghost" type="submit" name="_next" value="stay">Só salvar</button><?php endif; ?>
        </div>
    </form>

<?php elseif ($step === 2): ?>
    <form class="card card-pad form" method="post" action="<?= e($action(2)) ?>" novalidate>
        <?= csrf_field() ?>
        <h2 class="panel-title mb-0">Apresentação</h2>
        <?= view('field', ['name' => 'description', 'label' => 'Descrição completa', 'type' => 'textarea', 'value' => $val('description'), 'hint' => 'Pelo menos 80 caracteres. Explique o estilo, a experiência e o que a empresa pode esperar. Dados de contato não são permitidos.', 'attrs' => 'required rows="7" minlength="80" maxlength="5000" data-count="5000"']) ?>
        <div class="form-grid">
            <?= view('field', ['name' => 'what_company_buys', 'label' => 'O que a empresa contrata', 'type' => 'textarea', 'optional' => true, 'value' => $val('what_company_buys'), 'attrs' => 'rows="4" maxlength="1500"']) ?>
            <?= view('field', ['name' => 'what_company_provides', 'label' => 'O que a empresa precisa enviar', 'type' => 'textarea', 'optional' => true, 'value' => $val('what_company_provides'), 'attrs' => 'rows="4" maxlength="1500"']) ?>
            <?= view('field', ['name' => 'how_it_works', 'label' => 'Como funciona a gravação', 'type' => 'textarea', 'optional' => true, 'value' => $val('how_it_works'), 'attrs' => 'rows="4" maxlength="1500"']) ?>
            <?= view('field', ['name' => 'framing', 'label' => 'Enquadramento e estilo', 'type' => 'textarea', 'optional' => true, 'value' => $val('framing'), 'attrs' => 'rows="4" maxlength="1500"']) ?>
        </div>
        <?= view('field', ['name' => 'additional_info', 'label' => 'Informações adicionais', 'type' => 'textarea', 'optional' => true, 'value' => $val('additional_info'), 'attrs' => 'rows="3" maxlength="1500"']) ?>
        <?= view('field', ['name' => 'video_url', 'label' => 'Vídeo de apresentação', 'type' => 'url', 'optional' => true, 'value' => $val('video_url'), 'hint' => 'Link do YouTube ou do Vimeo.', 'attrs' => 'maxlength="300" placeholder="https://www.youtube.com/watch?v=…"']) ?>

        <fieldset class="stack-sm">
            <legend>Perguntas frequentes <small class="muted">(opcional, até 8)</small></legend>
            <div class="stack-sm" data-repeat="faq" data-max="8">
                <?php foreach ($faqRows as $faq): ?>
                    <div class="repeat-row" data-repeat-row>
                        <button class="btn btn-link row-remove" type="button" data-repeat-remove>Remover</button>
                        <label class="field"><span>Pergunta</span><input name="faq_question[]" maxlength="200" value="<?= e($faq['question']) ?>"></label>
                        <label class="field"><span>Resposta</span><textarea name="faq_answer[]" rows="2" maxlength="1000"><?= e($faq['answer']) ?></textarea></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div><button class="btn btn-ghost btn-sm" type="button" data-repeat-add="faq" data-keep-enabled>Adicionar pergunta</button></div>
        </fieldset>
        <template data-repeat-template="faq">
            <div class="repeat-row" data-repeat-row>
                <button class="btn btn-link row-remove" type="button" data-repeat-remove>Remover</button>
                <label class="field"><span>Pergunta</span><input name="faq_question[]" maxlength="200"></label>
                <label class="field"><span>Resposta</span><textarea name="faq_answer[]" rows="2" maxlength="1000"></textarea></label>
            </div>
        </template>

        <div class="form-actions">
            <button class="btn btn-ink" type="submit">Salvar e continuar</button>
            <button class="btn btn-ghost" type="submit" name="_next" value="stay">Só salvar</button>
        </div>
    </form>

    <section class="card card-pad" style="margin-top:1rem" aria-labelledby="fotos">
        <div class="row-between"><h2 id="fotos" class="panel-title mb-0">Fotos</h2><span class="muted small"><?= count($images) ?> de <?= (int) $limits['max_images'] ?></span></div>
        <p class="muted small">JPG, PNG ou WebP de até <?= (int) config('uploads.image_max_mb', 5) ?> MB. A primeira foto vira a capa do anúncio. Os metadados das fotos (como localização) são removidos.</p>
        <?php if ($images === []): ?>
            <p class="alert alert-info">Adicione pelo menos uma foto para enviar o anúncio para revisão.</p>
        <?php else: ?>
            <div class="image-grid" style="margin-bottom:1rem">
                <?php foreach ($images as $img): ?>
                    <div class="image-tile">
                        <img src="<?= e(media($img['thumb_path'] ?: $img['path'])) ?>" alt="<?= e($img['alt_text'] ?? '') ?>">
                        <div class="row">
                            <?php if ((int) $img['is_primary'] === 1): ?>
                                <span class="badge">Capa</span>
                            <?php else: ?>
                                <form method="post" action="<?= e(url($base . '/imagens/' . (int) $img['id'] . '/principal')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-link small" type="submit">Usar como capa</button></form>
                            <?php endif; ?>
                            <form method="post" action="<?= e(url($base . '/imagens/' . (int) $img['id'] . '/remover')) ?>" class="inline" data-confirm="Remover esta foto?"><?= csrf_field() ?><button class="btn btn-link small" type="submit" style="color:var(--err)">Remover</button></form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (count($images) < (int) $limits['max_images']): ?>
            <form class="dropzone" method="post" action="<?= e(url($base . '/imagens')) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <label class="field"><span>Escolher fotos</span><input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required></label>
                <label class="field"><span>Descrição das fotos <small>(para leitores de tela)</small></span><input name="alt_text" maxlength="140" placeholder="Ex.: Apresentadora em estúdio com fundo neutro"></label>
                <div><button class="btn btn-ghost" type="submit">Enviar fotos</button></div>
            </form>
        <?php endif; ?>
    </section>

<?php elseif ($step === 3): ?>
    <form class="card card-pad form" method="post" action="<?= e($action(3)) ?>" novalidate>
        <?= csrf_field() ?>
        <div>
            <h2 class="panel-title mb-0">Horas e preços</h2>
            <p class="muted small mb-0">Cada pacote é uma quantidade de horas de vídeo (de <?= (int) $limits['min_hours'] ?>h a <?= (int) $limits['max_hours'] ?>h) com preço, prazo e revisões. Até <?= (int) $limits['max_packages'] ?> pacotes. A plataforma retém <?= (int) setting('platform_fee_percent', 15) ?>% de cada contrato.</p>
        </div>
        <div class="stack-sm" data-repeat="packages" data-max="<?= (int) $limits['max_packages'] ?>">
            <?php foreach ($packageRows as $row): ?>
                <?= view('package-row', ['row' => $row, 'limits' => $limits]) ?>
            <?php endforeach; ?>
            <?php if ($packageRows === []): ?>
                <?= view('package-row', ['row' => [], 'limits' => $limits]) ?>
            <?php endif; ?>
        </div>
        <div><button class="btn btn-ghost btn-sm" type="button" data-repeat-add="packages" data-keep-enabled>Adicionar pacote</button></div>
        <template data-repeat-template="packages"><?= view('package-row', ['row' => [], 'limits' => $limits]) ?></template>
        <div class="form-actions">
            <button class="btn btn-ink" type="submit">Salvar e continuar</button>
            <button class="btn btn-ghost" type="submit" name="_next" value="stay">Só salvar</button>
        </div>
    </form>

<?php elseif ($step === 4): ?>
    <form class="card card-pad form" method="post" action="<?= e($action(4)) ?>" novalidate>
        <?= csrf_field() ?>
        <div>
            <h2 class="panel-title mb-0">Adicionais</h2>
            <p class="muted small mb-0">Extras que a empresa pode somar ao pacote, como roteiro, legenda ou versão vertical. Opcional, até 10.</p>
        </div>
        <div class="stack-sm" data-repeat="addons" data-max="10">
            <?php foreach ($addonRows as $row): ?><?= view('addon-row', ['row' => $row]) ?><?php endforeach; ?>
        </div>
        <?php if ($addonRows === []): ?><p class="muted small mb-0">Nenhum adicional cadastrado.</p><?php endif; ?>
        <div><button class="btn btn-ghost btn-sm" type="button" data-repeat-add="addons" data-keep-enabled>Adicionar adicional</button></div>
        <template data-repeat-template="addons"><?= view('addon-row', ['row' => []]) ?></template>
        <div class="form-actions">
            <button class="btn btn-ink" type="submit">Salvar e continuar</button>
            <button class="btn btn-ghost" type="submit" name="_next" value="stay">Só salvar</button>
        </div>
    </form>

<?php else: ?>
    <?php $ready = !in_array(false, array_column($checklist, 'ok'), true); ?>
    <div class="grid-main">
        <section class="card card-pad">
            <h2 class="panel-title">Publicação</h2>
            <ul class="checklist" style="margin-bottom:1.2rem">
                <?php foreach ($checklist as $item): ?>
                    <li class="<?= $item['ok'] ? 'done' : '' ?>">
                        <span class="tick" aria-hidden="true"><?= $item['ok'] ? '✓' : '' ?></span>
                        <?php if ($item['ok']): ?>
                            <span><?= e($item['label']) ?> <span class="sr-only">(completo)</span></span>
                        <?php else: ?>
                            <a href="<?= e(url($base . '?etapa=' . $item['step'])) ?>"><?= e($item['label']) ?></a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if (in_array($listing['status'], ['draft', 'rejected'], true)): ?>
                <p class="muted small"><?= setting('require_listing_approval', '1') === '1' ? 'Depois de enviado, o anúncio passa pela moderação. Você recebe uma notificação com a resposta.' : 'O anúncio é publicado assim que for enviado.' ?></p>
                <form method="post" action="<?= e(url($base . '/enviar')) ?>" class="row">
                    <?= csrf_field() ?>
                    <button class="btn btn-accent" type="submit" <?= $ready ? '' : 'disabled' ?>><?= $listing['status'] === 'rejected' ? 'Reenviar para revisão' : 'Enviar para revisão' ?></button>
                    <a class="btn btn-ghost" href="<?= e(url($base . '/previa')) ?>">Pré-visualizar</a>
                </form>
                <?php if (!$ready): ?><p class="small muted" style="margin-top:.5rem">Complete os itens da lista para liberar o envio.</p><?php endif; ?>
            <?php elseif ($listing['status'] === 'pending'): ?>
                <div class="alert alert-info mb-0">Em revisão desde <?= e(fmt_datetime($listing['submitted_at'])) ?>. Enquanto isso, você pode continuar editando.</div>
            <?php elseif ($listing['status'] === 'active'): ?>
                <div class="row">
                    <a class="btn btn-ghost" href="<?= e(listing_url($listing['slug'])) ?>">Ver no site</a>
                    <form method="post" action="<?= e(url($base . '/pausar')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-ghost" type="submit">Pausar anúncio</button></form>
                </div>
            <?php elseif ($listing['status'] === 'paused'): ?>
                <form method="post" action="<?= e(url($base . '/reativar')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-accent" type="submit">Reativar anúncio</button></form>
            <?php endif; ?>

            <hr style="border:0;border-top:1px solid var(--line);margin:1.4rem 0">
            <div class="row">
                <form method="post" action="<?= e(url($base . '/duplicar')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" type="submit">Duplicar anúncio</button></form>
                <form method="post" action="<?= e(url($base . '/excluir')) ?>" class="inline" data-confirm="Excluir este anúncio? Ele sai do site e não pode ser recuperado pelo painel."><?= csrf_field() ?><button class="btn btn-danger btn-sm" type="submit">Excluir anúncio</button></form>
            </div>
        </section>

        <section class="card card-pad">
            <h2 class="panel-title">Histórico de moderação</h2>
            <?php if ($history === []): ?>
                <p class="muted small mb-0">Ainda não enviado para revisão.</p>
            <?php else: ?>
                <ol class="timeline">
                    <?php $labels = ['submitted' => 'Enviado para revisão', 'approved' => 'Aprovado', 'rejected' => 'Reprovado', 'paused' => 'Pausado', 'reactivated' => 'Reativado', 'deleted' => 'Excluído']; ?>
                    <?php foreach ($history as $h): ?>
                        <li>
                            <strong><?= e($labels[$h['action']] ?? $h['action']) ?></strong>
                            <small><?= e(fmt_datetime($h['created_at'])) ?> · <?= $h['actor_role'] === 'admin' ? 'Moderação' : 'Você' ?></small>
                            <?php if ($h['reason']): ?><p class="pre"><?= e($h['reason']) ?></p><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    </div>
<?php endif; ?>

<?php if (!$isNew && $step < 5 && $step > 1): ?>
    <p style="margin-top:1rem"><a class="text-link small" href="<?= e(url($base . '?etapa=' . ($step - 1))) ?>">← Voltar para a etapa anterior</a></p>
<?php endif; ?>
