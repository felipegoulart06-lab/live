<div class="page-title">
    <div>
        <h1>Favoritos</h1>
        <p>Anúncios e criadores que você salvou para comparar depois.</p>
    </div>
</div>
<?php if ($listings === [] && $creators === []): ?>
    <?= view('empty', ['title' => 'Nada salvo ainda', 'message' => 'Use o botão "Salvar" nos anúncios e perfis para montar sua lista.', 'action' => 'Ver anúncios', 'actionUrl' => url('/anuncios')]) ?>
<?php endif; ?>
<?php if ($listings !== []): ?>
    <section style="margin-bottom:1.5rem">
        <h2 class="panel-title">Anúncios (<?= count($listings) ?>)</h2>
        <div class="card-grid">
            <?php foreach ($listings as $item): ?><?= view('listing-card', ['item' => $item]) ?><?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
<?php if ($creators !== []): ?>
    <section>
        <h2 class="panel-title">Criadores (<?= count($creators) ?>)</h2>
        <div class="card-grid">
            <?php foreach ($creators as $item): ?><?= view('creator-card', ['item' => $item]) ?><?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
