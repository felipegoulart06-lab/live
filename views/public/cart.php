<?php
$isCompany = $authUser && $authUser->isCompany();
$canPay = $isCompany && $lines !== [];
$loginNext = '/carrinho';
$registerNext = '/cadastro?tipo=empresa';
?>
<div class="page-head">
    <h1>Carrinho</h1>
    <p class="lead mb-0"><?= $lines === [] ? 'Seu carrinho está vazio.' : 'Revise os extras e pague com Mercado Pago. Visitantes podem montar o pedido; só a conta da empresa finaliza.' ?></p>
</div>

<?php if ($lines === []): ?>
    <?= view('empty', ['title' => 'Nenhum item no carrinho', 'message' => 'Escolha as horas e os adicionais no anúncio. O carrinho guarda tudo, mesmo sem login.', 'action' => 'Ver anúncios', 'actionUrl' => url('/anuncios')]) ?>
<?php else: ?>
    <div class="pay-grid">
        <div class="stack">
            <?php foreach ($lines as $line): ?>
                <article class="card card-pad cart-line">
                    <div class="pay-item mb-0">
                        <?php if ($line['listing_cover'] !== ''): ?>
                            <img src="<?= e(media($line['listing_cover'])) ?>" alt="">
                        <?php endif; ?>
                        <div class="grow">
                            <p class="mb-0"><a href="<?= e(url('/anuncios/' . $line['listing_slug'])) ?>"><strong><?= e($line['listing_title']) ?></strong></a></p>
                            <p class="muted small mb-0"><?= e(public_first_name($line['creator_name'])) ?> · <?= (int) $line['hours'] ?>h · prazo <?= (int) $line['delivery_days'] ?> dias</p>
                        </div>
                        <form method="post" action="<?= e(url('/carrinho/remover')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($line['id']) ?>">
                            <button class="btn btn-ghost btn-sm" type="submit">Remover</button>
                        </form>
                    </div>
                    <div class="money-lines" style="margin-top:0.85rem">
                        <div><span>Pacote de <?= (int) $line['hours'] ?>h</span><span><?= e(money($line['package_price_cents'])) ?></span></div>
                        <?php foreach ($line['addons'] as $addon): ?>
                            <div><span><?= e($addon['name']) ?></span><span><?= e(money($addon['price_cents'])) ?></span></div>
                        <?php endforeach; ?>
                        <div class="total"><span>Subtotal</span><span><?= e(money($line['total_cents'])) ?></span></div>
                    </div>
                </article>
            <?php endforeach; ?>
            <a class="text-link" href="<?= e(url('/anuncios')) ?>">Continuar escolhendo anúncios</a>
        </div>
        <aside class="stack">
            <section class="card card-pad">
                <h2 class="panel-title">Resumo</h2>
                <div class="money-lines">
                    <div class="total"><span><?= count($lines) === 1 ? 'Total' : count($lines) . ' itens' ?></span><span><?= e(money($totalCents)) ?></span></div>
                </div>
                <?php if ($canPay): ?>
                    <form method="post" action="<?= e(url('/carrinho/fechar')) ?>" style="margin-top:1rem">
                        <?= csrf_field() ?>
                        <button class="btn btn-buy btn-block" type="submit">Pagar com Mercado Pago</button>
                    </form>
                    <p class="privacy-note" style="margin-top:0.7rem">Cada anúncio vira um pedido. Se houver mais de um, você paga um e o restante fica no carrinho.</p>
                <?php elseif ($authUser): ?>
                    <p class="privacy-note" style="margin-top:1rem">Você está conectado como <?= $authUser->isCreator() ? 'criador' : 'administrador' ?>. Entre com a <strong>conta da empresa</strong> para pagar.</p>
                <?php else: ?>
                    <a class="btn btn-buy btn-block" style="margin-top:1rem" href="<?= e(url('/login?next=' . rawurlencode($loginNext))) ?>">Entrar para pagar</a>
                    <a class="btn btn-ghost btn-block" href="<?= e(url($registerNext)) ?>">Criar conta de empresa</a>
                    <p class="privacy-note">O carrinho já está salvo neste aparelho. Depois do login você segue ao checkout transparente.</p>
                <?php endif; ?>
            </section>
        </aside>
    </div>
<?php endif; ?>
