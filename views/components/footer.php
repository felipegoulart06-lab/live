<footer class="site-footer">
    <div class="footer-grid">
        <div>
            <p class="brand"><?= e(setting('platform_name', 'Nexo')) ?></p>
            <p class="muted"><?= e(setting('tagline', 'Profissionais certos, no ritmo do seu projeto.')) ?></p>
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
            <p class="footer-heading">Para profissionais</p>
            <a href="<?= e(url('/criar-conta?intent=seller')) ?>">Começar a vender</a>
            <a href="<?= e(url('/p/como-funciona')) ?>">Como funciona</a>
        </div>
        <div>
            <p class="footer-heading">Newsletter</p>
            <form class="newsletter" method="post" action="<?= e(url('/buscar')) ?>" onsubmit="event.preventDefault(); window.Nexo && Nexo.toast('Cadastro de newsletter entra na próxima fase.');">
                <?= csrf_field() ?>
                <label class="sr-only" for="news-email">E-mail</label>
                <input id="news-email" type="email" name="email" placeholder="Seu e-mail" required>
                <button class="btn btn-ink" type="submit">Assinar</button>
            </form>
        </div>
    </div>
    <p class="footer-copy">© <?= date('Y') ?> <?= e(setting('platform_name', 'Nexo')) ?>. Marketplace de serviços profissionais.</p>
</footer>
