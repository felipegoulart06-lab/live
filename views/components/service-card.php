<?php
$service = $service ?? [];
$href = service_url($service);
?>
<article class="card service-card">
    <div class="thumb <?= empty($service['cover_path']) ? 'thumb-empty' : '' ?>">
        <?php if (!empty($service['cover_path'])): ?>
            <img src="<?= e(media($service['cover_path'])) ?>" alt="<?= e($service['title']) ?>" width="480" height="280" loading="lazy">
        <?php endif; ?>
    </div>
    <div class="card-body">
        <p class="eyebrow"><?= e($service['category_name'] ?? '') ?></p>
        <h3><a href="<?= e($href) ?>"><?= e($service['title'] ?? '') ?></a></h3>
        <p class="muted clamp"><?= e($service['short_description'] ?? '') ?></p>
        <div class="meta-row">
            <span><?= e($service['display_name'] ?? '') ?><?php if (!empty($service['is_verified'])): ?> · verificado<?php endif; ?></span>
            <span><?= number_format((float) ($service['rating_avg'] ?? 0), 1, ',', '.') ?> (<?= (int) ($service['rating_count'] ?? 0) ?>)</span>
        </div>
        <div class="price-row">
            <strong><?= money($service['starting_price_cents'] ?? 0) ?></strong>
            <span class="muted"><?= (int) ($service['min_delivery_days'] ?? 0) ?> dias</span>
        </div>
    </div>
</article>
