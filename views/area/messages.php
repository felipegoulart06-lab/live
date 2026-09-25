<?php
$isCreator = $area === '/painel';
$hasThread = isset($conversation);
?>
<div class="page-title">
    <div>
        <h1>Mensagens</h1>
        <p>Converse dentro da plataforma. Telefones, e-mails e links de contato externo são ocultados automaticamente.</p>
    </div>
</div>

<?php if ($result['rows'] === [] && !$hasThread): ?>
    <?= view('empty', $isCreator
        ? ['title' => 'Nenhuma conversa ainda', 'message' => 'As empresas podem mandar perguntas pelos seus anúncios. Elas aparecem aqui.']
        : ['title' => 'Nenhuma conversa ainda', 'message' => 'Mande uma pergunta a um criador pela página do anúncio.', 'action' => 'Ver anúncios', 'actionUrl' => url('/anuncios')]) ?>
<?php else: ?>
    <div class="inbox<?= $hasThread ? ' has-thread' : '' ?>">
        <nav class="inbox-list" aria-label="Conversas">
            <?php foreach ($result['rows'] as $cv): ?>
                <?php $unread = $cv['last_message_at'] && (!$cv['my_read_at'] || $cv['my_read_at'] < $cv['last_message_at']); ?>
                <a href="<?= e(url($area . '/mensagens/' . $cv['uuid'])) ?>" class="<?= (int) $cv['id'] === $activeId ? 'is-active' : '' ?><?= $unread ? ' is-unread' : '' ?>" <?= (int) $cv['id'] === $activeId ? 'aria-current="page"' : '' ?>>
                    <strong><?= e($isCreator ? $cv['company_name'] : $cv['creator_name']) ?><?= $unread ? '<span class="sr-only"> (não lida)</span>' : '' ?></strong>
                    <small><?= e($cv['listing_title'] ?? 'Conversa geral') ?> · <?= e(time_ago($cv['last_message_at'])) ?></small>
                    <span class="snippet"><?= e(mb_substr((string) $cv['last_body'], 0, 90)) ?></span>
                </a>
            <?php endforeach; ?>
            <?php if ((int) $result['pages'] > 1): ?><div style="padding:0 1rem 1rem"><?= view('pager', ['result' => $result]) ?></div><?php endif; ?>
        </nav>

        <section class="thread" aria-label="Conversa">
            <?php if (!$hasThread): ?>
                <div class="thread-body" style="justify-content:center;align-items:center"><p class="muted">Escolha uma conversa.</p></div>
            <?php else: ?>
                <header class="thread-head">
                    <a class="back-link only-mobile" href="<?= e(url($area . '/mensagens')) ?>">← Conversas</a>
                    <strong><?= e($isCreator ? $conversation['company_name'] : $conversation['creator_name']) ?></strong>
                    <?php if ($conversation['listing_slug']): ?><br><small>Sobre <a href="<?= e(listing_url($conversation['listing_slug'])) ?>"><?= e($conversation['listing_title']) ?></a></small><?php endif; ?>
                </header>
                <div class="thread-body" aria-live="polite">
                    <?php if ($messages === []): ?><p class="muted">Nenhuma mensagem ainda.</p><?php endif; ?>
                    <?php foreach ($messages as $m): ?>
                        <div class="bubble<?= (int) $m['sender_id'] === $authUser->id ? ' is-mine' : '' ?>">
                            <?= e($m['body']) ?>
                            <small><?= (int) $m['sender_id'] === $authUser->id ? 'Você' : e($m['sender_name']) ?> · <?= e(fmt_datetime($m['created_at'])) ?></small>
                        </div>
                    <?php endforeach; ?>
                    <span id="fim"></span>
                </div>
                <form class="thread-form" method="post" action="<?= e(url($area . '/mensagens/' . $conversation['uuid'])) ?>">
                    <?= csrf_field() ?>
                    <label class="sr-only" for="msg-body">Mensagem</label>
                    <textarea id="msg-body" name="body" rows="2" maxlength="2000" required placeholder="Escreva uma mensagem"></textarea>
                    <button class="btn btn-ink" type="submit">Enviar</button>
                </form>
                <?php if ($m = error_field('body')): ?><p class="field-err" style="padding:0 1rem 1rem"><?= e($m) ?></p><?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
<?php endif; ?>
