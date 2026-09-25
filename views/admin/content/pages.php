<?php
$bySlug = array_column($pages, null, 'slug');
?>
<div class="page-title"><div><h1>Páginas institucionais</h1><p>Textos de termos, privacidade, sobre, como funciona e contato. Separe parágrafos com uma linha em branco.</p></div></div>
<div class="grid-main">
    <?php if ($edit): ?>
        <form class="card card-pad form" method="post" action="<?= e(url('/admin/paginas')) ?>" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="slug" value="<?= e($edit['slug']) ?>">
            <div class="row-between">
                <h2 class="panel-title mb-0">/<?= e($edit['slug']) ?></h2>
                <?php if (($edit['status'] ?? '') === 'published'): ?><a class="small" href="<?= e(url('/' . $edit['slug'])) ?>" target="_blank" rel="noopener">Ver página</a><?php endif; ?>
            </div>
            <?= view('field', ['name' => 'title', 'label' => 'Título', 'value' => (string) $edit['title'], 'attrs' => 'required maxlength="120"']) ?>
            <?= view('field', ['name' => 'content', 'label' => 'Conteúdo', 'type' => 'textarea', 'value' => (string) $edit['content'], 'hint' => 'Texto simples. Links e HTML não são interpretados.', 'attrs' => 'required rows="16" maxlength="30000"']) ?>
            <?= view('field', ['name' => 'status', 'label' => 'Situação', 'type' => 'select', 'value' => (string) $edit['status'], 'options' => ['published' => 'Publicada', 'draft' => 'Rascunho (fora do ar)']]) ?>
            <div class="form-actions"><button class="btn btn-ink" type="submit">Salvar página</button><a class="btn btn-ghost" href="<?= e(url('/admin/paginas')) ?>">Voltar</a></div>
        </form>
    <?php else: ?>
        <div class="card">
            <ul class="list">
                <?php foreach ($slugs as $slug): $p = $bySlug[$slug] ?? null; ?>
                    <li>
                        <a href="<?= e(url('/admin/paginas?pagina=' . $slug)) ?>"><strong><?= e($p['title'] ?? $slug) ?></strong><small>/<?= e($slug) ?><?= $p ? ' · atualizada ' . e(time_ago($p['updated_at'])) : ' · ainda não criada' ?></small></a>
                        <?= $p ? status_badge('visibility', $p['status']) : '<span class="status status--neutral">Sem conteúdo</span>' ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <aside class="card card-pad">
        <h2 class="panel-title">Onde aparecem</h2>
        <p class="muted small mb-0">Todas as páginas ficam no rodapé do site. Rascunhos retornam "página não encontrada" para visitantes.</p>
    </aside>
</div>
