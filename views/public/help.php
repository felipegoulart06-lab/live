<?php
$groups = ['all' => 'Geral', 'company' => 'Para empresas', 'creator' => 'Para criadores'];
$byAudience = [];
foreach ($faqs as $faq) {
    $byAudience[$faq['audience']][] = $faq;
}
?>
<nav class="crumb" aria-label="Você está em"><a href="<?= e(url('/')) ?>">Início</a><span aria-hidden="true">/</span><span>Ajuda</span></nav>
<header class="page-head">
    <h1>Central de ajuda</h1>
    <p class="lead">Respostas sobre contratação, pagamento, anúncios e segurança. Não achou? <a href="<?= e(url('/contato')) ?>">Fale com a equipe</a>.</p>
</header>
<?php if ($faqs === []): ?>
    <?= view('empty', ['title' => 'Nenhuma pergunta publicada ainda']) ?>
<?php endif; ?>
<?php foreach ($groups as $key => $label): ?>
    <?php if (empty($byAudience[$key])) { continue; } ?>
    <section class="section" aria-labelledby="faq-<?= e($key) ?>" style="margin-top:1.5rem">
        <h2 id="faq-<?= e($key) ?>"><?= e($label) ?></h2>
        <div class="panel faq">
            <?php foreach ($byAudience[$key] as $faq): ?>
                <details><summary><?= e($faq['question']) ?></summary><p><?= e($faq['answer']) ?></p></details>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>
