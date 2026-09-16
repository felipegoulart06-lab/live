<?php
$service = $service ?? [];
$href = service_url($service);
$orders = (int) ($service['orders_count'] ?? 0);
$days = (int) ($service['min_delivery_days'] ?? 0);
$rating = (float) ($service['rating_avg'] ?? 0);
$isNew = $orders === 0;
?>
<article class="card service-card">
    <a class="thumb <?= empty($service['cover_path']) ? 'thumb-empty' : '' ?>" href="<?= e($href) ?>">
        <?php if (!empty($service['cover_path'])): ?>
            <img src="<?= e(media($service['cover_path'])) ?>" alt="<?= e($service['title']) ?>" width="480" height="280" loading="lazy">
        <?php endif; ?>
        <?php if ($isNew): ?><span class="badge-new">Novo</span><?php endif; ?>
    </a>
    <div class="card-body">
        <p class="seller-line"><?= e(seller_short_name($service['display_name'] ?? '')) ?></p>
        <h3><a href="<?= e($href) ?>"><?= e($service['title'] ?? '') ?></a></h3>
        <p class="price-row"><strong><?= money($service['starting_price_cents'] ?? 0) ?></strong></p>
        <p class="card-stats">
            <?php if ($isNew): ?>
                <span>Novo</span>
            <?php else: ?>
                <span><?= $orders ?></span>
                <span><?= number_format($rating, 2, '.', '') ?></span>
            <?php endif; ?>
            <span><?= $days ?>d</span>
        </p>
    </div>
</article>
