<footer class="site-footer">
    <div class="footer-grid">
        <div>
            <p class="brand"><?= e(brand_name()) ?></p>
            <p class="muted"><?= e(setting('tagline', 'Horas de vídeo para a sua empresa. Tema definido por quem paga.')) ?></p>
        </div>
        <div>
            <p class="footer-heading">Comunidade</p>
            <a href="<?= e(page_url('ajuda')) ?>">Suporte</a>
            <a href="<?= e(page_url('sobre')) ?>">Sobre</a>
            <a href="<?= e(page_url('como-funciona')) ?>">Como funciona</a>
            <a href="<?= e(url('/criar-conta?intent=seller')) ?>">Anunciar horas de vídeo</a>
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
                <a href="<?= e(page_url((string) $page['slug'])) ?>"><?= e($page['title']) ?></a>
            <?php endforeach; ?>
        </div>
        <div>
            <p class="footer-heading">Parceria</p>
            <a href="<?= e(url('/criar-conta?intent=seller')) ?>">Sou criador</a>
            <a href="<?= e(page_url('contato')) ?>">Contato</a>
        </div>
    </div>
    <p class="footer-copy">© <?= date('Y') ?> <?= e(brand_name()) ?>. Mapa do site · <a href="<?= e(page_url('termos')) ?>">Termos de uso</a> · <a href="<?= e(page_url('privacidade')) ?>">Política de privacidade</a> · <a href="<?= e(page_url('contato')) ?>">Contato</a></p>
</footer>
