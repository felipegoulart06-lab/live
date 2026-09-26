<?php
$toggles = ['require_listing_approval', 'maintenance_mode'];
$numbers = ['platform_fee_percent', 'request_expiry_hours', 'min_package_hours', 'max_package_hours', 'max_packages_per_listing', 'max_images_per_listing'];
?>
<div class="page-title"><div><h1>Configurações</h1><p>Regras gerais do marketplace. Toda alteração fica registrada na auditoria com o valor anterior.</p></div></div>
<?php if (!$canEdit): ?><div class="alert alert-info">Você pode consultar as configurações. Apenas administradores master podem alterá-las.</div><?php endif; ?>
<div class="grid-main">
    <form class="card card-pad form" method="post" action="<?= e(url('/admin/configuracoes')) ?>" novalidate>
        <?= csrf_field() ?>
        <fieldset class="form-grid" <?= $canEdit ? '' : 'disabled' ?>>
            <?php foreach ($fields as $key => [$label]): ?>
                <?php if (in_array($key, $toggles, true)): ?>
                    <?= view('field', ['name' => $key, 'label' => $label, 'type' => 'select', 'value' => $values[$key], 'options' => ['1' => 'Sim', '0' => 'Não'],
                        'hint' => $key === 'maintenance_mode' ? 'Com manutenção ligada, só administradores acessam o site.' : 'Desligado, anúncios são publicados assim que o criador envia.']) ?>
                <?php elseif (in_array($key, $numbers, true)): ?>
                    <?= view('field', ['name' => $key, 'label' => $label, 'type' => 'number', 'value' => $values[$key], 'attrs' => 'required min="0"',
                        'hint' => $key === 'platform_fee_percent' ? 'Vale para contratos criados a partir de agora.' : null]) ?>
                <?php elseif ($key === 'meta_description'): ?>
                    <?= view('field', ['name' => $key, 'label' => $label, 'type' => 'textarea', 'span' => true, 'value' => $values[$key], 'attrs' => 'required rows="2" maxlength="160" data-count="160"']) ?>
                <?php else: ?>
                    <?= view('field', ['name' => $key, 'label' => $label, 'type' => $key === 'support_email' ? 'email' : 'text', 'value' => $values[$key], 'attrs' => 'required']) ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </fieldset>
        <?php if ($canEdit): ?><div class="form-actions"><button class="btn btn-ink" type="submit">Salvar configurações</button></div><?php endif; ?>
    </form>

    <section class="card card-pad">
        <h2 class="panel-title">Infraestrutura</h2>
        <dl class="kv">
            <dt>Banco de dados</dt><dd><?= e($database) ?></dd>
            <dt>Arquivos</dt><dd><?= $storageReady ? 'Supabase Storage' : 'Disco local' ?></dd>
            <dt>Pagamentos</dt><dd><?= !empty($mpReady) && !empty($mpSetup['webhook_secret']) && !empty($mpSetup['https_url']) ? 'Checkout transparente Mercado Pago (Orders)' : 'Falta configurar o Mercado Pago' ?></dd>
        </dl>
        <?php if (!$storageReady): ?><p class="muted small" style="margin:.8rem 0 0">Em produção, defina SUPABASE_URL e SUPABASE_SERVICE_ROLE_KEY para guardar uploads fora do servidor.</p><?php endif; ?>
        <h3 class="panel-title" style="margin-top:1.2rem">Mercado Pago (app 50conto)</h3>
        <ol class="muted small" style="margin:.4rem 0 0;padding-left:1.2rem">
            <li><?= !empty($mpSetup['public_key']) ? 'Chave pública de teste no ambiente.' : 'Ative as credenciais de teste e cole MP_PUBLIC_KEY na Vercel.' ?></li>
            <li><?= !empty($mpSetup['access_token']) ? 'Access token de teste no servidor.' : 'Cole MP_ACCESS_TOKEN (APP_USR-…) só na Vercel, nunca no git.' ?></li>
            <li><?= !empty($mpSetup['https_url']) ? 'APP_URL em HTTPS.' : 'Defina APP_URL=https://live-zeta-mocha.vercel.app (ou o domínio final).' ?></li>
            <li><?= !empty($mpSetup['webhook_secret']) ? 'Secret do webhook cadastrado.' : 'Em Webhooks, tópico Order, URL abaixo; cole o secret em MP_WEBHOOK_SECRET.' ?></li>
        </ol>
        <p class="muted small" style="margin:.6rem 0 0">Webhook: <code><?= e($mpWebhook) ?></code></p>
    </section>
</div>
