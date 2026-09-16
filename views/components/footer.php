<footer class="site-footer">
    <div class="footer-grid">
        <div>
            <p class="brand"><?= e(brand_name()) ?></p>
            <p class="muted"><?= e(setting('tagline', 'Preço imbatível. Diversidade inigualável.')) ?></p>
        </div>
        <div>
            <p class="footer-heading">Comunidade</p>
            <a href="<?= e(url('/p/ajuda')) ?>">Suporte</a>
            <a href="<?= e(url('/p/sobre')) ?>">Sobre</a>
            <a href="<?= e(url('/p/como-funciona')) ?>">Como funciona</a>
            <a href="<?= e(url('/criar-conta?intent=seller')) ?>">Estamos contratando</a>
        </div>
        <div>
            <p class="footer-heading">Plataforma</p>
            <?php
            $footerPages = $footerPages ?? [];
            if ($footerPages === []) {
                try {
                    $footerPages = (new \App\Repositories\PageRepository())->published();
                } catch (\Throwable) {
                    $footerPages = [];
                }
            }
            foreach ($footerPages as $page): ?>
                <a href="<?= e(url('/p/' . $page['slug'])) ?>"><?= e($page['title']) ?></a>
            <?php endforeach; ?>
        </div>
        <div>
            <p class="footer-heading">Parceria</p>
            <a href="<?= e(url('/criar-conta?intent=seller')) ?>">Começar a vender</a>
            <a href="<?= e(url('/p/contato')) ?>">Contato</a>
        </div>
    </div>
    <p class="footer-copy">© <?= date('Y') ?> <?= e(brand_name()) ?>. Mapa do site · <a href="<?= e(url('/p/termos')) ?>">Termos de uso</a> · <a href="<?= e(url('/p/privacidade')) ?>">Política de privacidade</a> · <a href="<?= e(url('/p/contato')) ?>">Contato</a></p>
</footer>
