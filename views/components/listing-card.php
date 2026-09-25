<?php /** @var array<string, mixed> $item */ ?>
<article class="listing-card">
    <a class="thumb<?= empty($item['cover_path']) ? ' thumb-empty' : '' ?>" href="<?= e(listing_url($item['slug'])) ?>" tabindex="-1" aria-hidden="true">
        <?php if (!empty($item['cover_path'])): ?>
            <img src="<?= e(media($item['cover_path'])) ?>" alt="" loading="lazy">
        <?php else: ?>
            <span>Sem imagem</span>
        <?php endif; ?>
        <?php if (!empty($item['category_name'])): ?><span class="thumb-badge"><?= e($item['category_name']) ?></span><?php endif; ?>
    </a>
    <div class="card-body">
        <p class="seller-line">
            <?= e($item['display_name']) ?>
            <?php if (!empty($item['is_verified'])): ?><span class="badge badge-verified">Verificado</span><?php endif; ?>
        </p>
        <h3><a class="clamp" href="<?= e(listing_url($item['slug'])) ?>"><?= e($item['title']) ?></a></h3>
        <p class="card-meta">
            <?php if ((int) $item['rating_count'] > 0): ?>
                <span><span class="stars" aria-hidden="true">★</span> <?= number_format((float) $item['rating_avg'], 1, ',', '') ?> (<?= (int) $item['rating_count'] ?>)</span>
            <?php else: ?>
                <span>Sem avaliações</span>
            <?php endif; ?>
            <?php if (!empty($item['min_delivery_days'])): ?><span>Entrega em <?= (int) $item['min_delivery_days'] ?> dias</span><?php endif; ?>
        </p>
        <div class="price-row">
            <span class="muted"><?= !empty($item['min_hours']) ? 'A partir de ' . (int) $item['min_hours'] . 'h' : 'A partir de' ?></span>
            <strong><?= e(money($item['starting_price_cents'])) ?></strong>
        </div>
    </div>
</article>
