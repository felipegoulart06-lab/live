<?php /** @var array<string, mixed> $item */ ?>
<a class="creator-card" href="<?= e(creator_url($item['slug'])) ?>">
    <?php if (!empty($item['avatar_path'])): ?>
        <img class="avatar" src="<?= e(media($item['avatar_path'])) ?>" alt="" loading="lazy">
    <?php else: ?>
        <span class="avatar" aria-hidden="true"><?= e(initials($item['display_name'])) ?></span>
    <?php endif; ?>
    <h3><?= e($item['display_name']) ?></h3>
    <?php if (!empty($item['is_verified'])): ?><span class="badge badge-verified">Verificado</span><?php endif; ?>
    <p class="muted small clamp mb-0"><?= e($item['headline'] ?? '') ?></p>
    <p class="card-meta">
        <?php if (!empty($item['city'])): ?><span><?= e($item['city'] . ($item['state'] ? '/' . $item['state'] : '')) ?></span><?php endif; ?>
        <?php if ((int) $item['rating_count'] > 0): ?><span><span class="stars" aria-hidden="true">★</span> <?= number_format((float) $item['rating_avg'], 1, ',', '') ?></span><?php endif; ?>
        <span><?= (int) $item['contracts_count'] ?> contratos</span>
    </p>
    <?php if (!empty($item['starting_price_cents'])): ?><p class="small mb-0">A partir de <strong><?= e(money($item['starting_price_cents'])) ?></strong></p><?php endif; ?>
</a>
