<?php $n = (int) ($cartCount ?? 0); ?>
<a class="cart-fab<?= $n > 0 ? ' has-items' : '' ?>" href="<?= e(url('/carrinho')) ?>" aria-label="<?= $n > 0 ? 'Carrinho, ' . $n . ($n === 1 ? ' item' : ' itens') : 'Carrinho' ?>">
    <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false">
        <path fill="currentColor" d="M7 18a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm10 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4ZM6.2 6l.4 2h12.7l-1.2 6.2a2 2 0 0 1-2 1.6H9.1a2 2 0 0 1-2-1.6L5.2 4H3V2h3.2a1 1 0 0 1 1 .8L7.4 4H21l-1.7 8.6A4 4 0 0 1 15.4 16H9.1a4 4 0 0 1-3.9-3.2L4.2 6h2Z"/>
    </svg>
    <span>Carrinho</span>
    <?php if ($n > 0): ?><span class="count"><?= $n > 99 ? '99+' : $n ?></span><?php endif; ?>
</a>
