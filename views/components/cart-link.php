<?php $n = (int) ($cartCount ?? 0); ?>
<a class="cart-link" href="<?= e(url('/carrinho')) ?>">
    Carrinho<?php if ($n > 0): ?> <span class="count"><?= $n > 99 ? '99+' : $n ?></span><?php endif; ?>
</a>
