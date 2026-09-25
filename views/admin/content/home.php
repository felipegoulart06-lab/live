<?php $hero = (string) setting('home_hero_image', ''); ?>
<div class="page-title">
    <div><h1>Conteúdo da home</h1><p>Textos principais da página inicial. As seções de anúncios são definidas em <a href="<?= e(url('/admin/destaques')) ?>">Destaques</a>.</p></div>
    <a class="btn btn-ghost" href="<?= e(url('/')) ?>">Ver a home</a>
</div>
<form class="card card-pad form" method="post" action="<?= e(url('/admin/conteudo')) ?>" enctype="multipart/form-data" style="max-width:760px" novalidate>
    <?= csrf_field() ?>
    <?= view('field', ['name' => 'home_hero_title', 'label' => 'Título principal', 'value' => $values['home_hero_title'], 'attrs' => 'required minlength="10" maxlength="90" data-count="90"']) ?>
    <?= view('field', ['name' => 'home_hero_subtitle', 'label' => 'Subtítulo', 'type' => 'textarea', 'value' => $values['home_hero_subtitle'], 'attrs' => 'required minlength="20" maxlength="300" rows="3" data-count="300"']) ?>
    <?= view('field', ['name' => 'platform_tagline', 'label' => 'Frase do rodapé', 'value' => $values['platform_tagline'], 'attrs' => 'required minlength="10" maxlength="160"']) ?>
    <div class="field">
        <span>Imagem principal</span>
        <?php if ($hero !== ''): ?><img src="<?= e(media($hero)) ?>" alt="Imagem atual da home" style="max-width:320px;border-radius:var(--radius);border:1px solid var(--line)"><?php endif; ?>
        <input type="file" name="home_hero_image" accept="image/jpeg,image/png,image/webp" aria-label="Nova imagem principal">
        <small>Deixe em branco para manter a atual.</small>
    </div>
    <div class="form-actions"><button class="btn btn-ink" type="submit">Salvar</button></div>
</form>
