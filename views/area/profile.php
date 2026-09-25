<?php
$isCreator = $area === '/painel';
$p = $profile;
$v = static fn (array $row, string $k): string => (string) ($row[$k] ?? '');
?>
<div class="page-title">
    <div>
        <h1>Perfil</h1>
        <p><?= $isCreator ? 'As informações abaixo aparecem no seu perfil público e nos seus anúncios, exceto telefone e e-mail.' : 'Dados da empresa. Telefone, CNPJ e e-mail não são exibidos para os criadores.' ?></p>
    </div>
    <?php if ($isCreator): ?><a class="btn btn-ghost" href="<?= e(creator_url($p['slug'])) ?>">Ver perfil público</a><?php endif; ?>
</div>

<div class="grid-main">
    <div class="stack">
        <form class="card card-pad form" method="post" action="<?= e(url($area . '/perfil')) ?>" novalidate>
            <?= csrf_field() ?>
            <h2 class="panel-title mb-0"><?= $isCreator ? 'Dados do perfil' : 'Dados da empresa' ?></h2>
            <div class="form-grid">
                <?= view('field', ['name' => 'display_name', 'label' => $isCreator ? 'Nome público' : 'Seu nome', 'value' => $v($p, 'display_name'), 'attrs' => 'required maxlength="80"']) ?>
                <?php if ($isCreator): ?>
                    <?= view('field', ['name' => 'headline', 'label' => 'Título profissional', 'optional' => true, 'value' => $v($p, 'headline'), 'attrs' => 'maxlength="120" placeholder="Ex.: Apresentadora para institucional"']) ?>
                    <?= view('field', ['name' => 'bio', 'label' => 'Biografia', 'type' => 'textarea', 'span' => true, 'value' => $v($p, 'bio'), 'hint' => 'Conte sua experiência em frente à câmera. Dados de contato não são permitidos.', 'attrs' => 'rows="5" maxlength="2000" data-count="2000"']) ?>
                    <?= view('field', ['name' => 'specialties', 'label' => 'Especialidades', 'optional' => true, 'value' => $v($p, 'specialties'), 'hint' => 'Separe por vírgula.', 'attrs' => 'maxlength="300"']) ?>
                    <?= view('field', ['name' => 'experience_years', 'label' => 'Anos de experiência', 'type' => 'number', 'optional' => true, 'value' => $v($p, 'experience_years'), 'attrs' => 'min="0" max="60"']) ?>
                <?php else: ?>
                    <?= view('field', ['name' => 'company_name', 'label' => 'Nome da empresa', 'value' => $v($company, 'company_name'), 'attrs' => 'required maxlength="120"']) ?>
                    <?= view('field', ['name' => 'legal_name', 'label' => 'Razão social', 'optional' => true, 'value' => $v($company, 'legal_name'), 'attrs' => 'maxlength="160"']) ?>
                    <?= view('field', ['name' => 'document', 'label' => 'CNPJ', 'optional' => true, 'value' => $v($company, 'document'), 'attrs' => 'maxlength="20" inputmode="numeric"']) ?>
                    <?= view('field', ['name' => 'responsible_name', 'label' => 'Responsável', 'value' => $v($company, 'responsible_name'), 'attrs' => 'required maxlength="80"']) ?>
                    <?= view('field', ['name' => 'website', 'label' => 'Site', 'type' => 'url', 'optional' => true, 'value' => $v($company, 'website'), 'attrs' => 'maxlength="200" placeholder="https://"']) ?>
                <?php endif; ?>
                <?= view('field', ['name' => 'phone', 'label' => 'Telefone', 'optional' => true, 'value' => $v($p, 'phone'), 'hint' => 'Visível só para a equipe da plataforma.', 'attrs' => 'maxlength="30" inputmode="tel" autocomplete="tel"']) ?>
                <?= view('field', ['name' => 'city', 'label' => 'Cidade', 'optional' => true, 'value' => $v($p, 'city'), 'attrs' => 'maxlength="80"']) ?>
                <?= view('field', ['name' => 'state', 'label' => 'UF', 'optional' => true, 'value' => $v($p, 'state'), 'attrs' => 'maxlength="2" style="text-transform:uppercase"']) ?>
            </div>
            <div class="form-actions"><button class="btn btn-ink" type="submit">Salvar perfil</button></div>
        </form>

        <?php if ($isCreator): ?>
            <form class="card card-pad form" id="disponibilidade" method="post" action="<?= e(url('/painel/perfil/disponibilidade')) ?>">
                <?= csrf_field() ?>
                <h2 class="panel-title mb-0">Disponibilidade</h2>
                <p class="muted small mb-0">Dias e horários em que você costuma gravar. Aparece no anúncio para as empresas.</p>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th scope="col">Dia</th><th scope="col">Início</th><th scope="col">Fim</th></tr></thead>
                        <tbody>
                        <?php for ($d = 0; $d <= 6; $d++): $slot = $slots[$d] ?? null; ?>
                            <tr>
                                <td><label class="check"><input type="checkbox" name="day[<?= $d ?>]" value="1" <?= $slot ? 'checked' : '' ?>> <?= e(weekday_name($d)) ?></label></td>
                                <td><label class="sr-only" for="start-<?= $d ?>">Início <?= e(weekday_name($d)) ?></label><input class="input" id="start-<?= $d ?>" type="time" name="start[<?= $d ?>]" value="<?= e($slot ? substr($slot['start_time'], 0, 5) : '09:00') ?>"></td>
                                <td><label class="sr-only" for="end-<?= $d ?>">Fim <?= e(weekday_name($d)) ?></label><input class="input" id="end-<?= $d ?>" type="time" name="end[<?= $d ?>]" value="<?= e($slot ? substr($slot['end_time'], 0, 5) : '18:00') ?>"></td>
                            </tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                <div class="form-grid">
                    <?= view('field', ['name' => 'min_notice_hours', 'label' => 'Antecedência mínima (horas)', 'type' => 'number', 'value' => $v($creator, 'min_notice_hours'), 'attrs' => 'required min="0" max="720"']) ?>
                    <div></div>
                    <?= view('field', ['name' => 'unavailable_from', 'label' => 'Ausente de', 'type' => 'date', 'optional' => true, 'value' => $v($creator, 'unavailable_from')]) ?>
                    <?= view('field', ['name' => 'unavailable_until', 'label' => 'Ausente até', 'type' => 'date', 'optional' => true, 'value' => $v($creator, 'unavailable_until')]) ?>
                    <?= view('field', ['name' => 'unavailable_note', 'label' => 'Observação da ausência', 'optional' => true, 'span' => true, 'value' => $v($creator, 'unavailable_note'), 'attrs' => 'maxlength="200" placeholder="Ex.: Férias, volto a gravar dia 20"']) ?>
                </div>
                <div class="form-actions"><button class="btn btn-ink" type="submit">Salvar disponibilidade</button></div>
            </form>
        <?php endif; ?>

        <form class="card card-pad form" id="seguranca" method="post" action="<?= e(url($area . '/perfil/acesso')) ?>" novalidate>
            <?= csrf_field() ?>
            <h2 class="panel-title mb-0">Acesso e segurança</h2>
            <div class="form-grid">
                <?= view('field', ['name' => 'email', 'label' => 'E-mail de acesso', 'type' => 'email', 'span' => true, 'value' => $v($p, 'email'), 'attrs' => 'required autocomplete="email"']) ?>
                <?= view('field', ['name' => 'password', 'label' => 'Nova senha', 'type' => 'password', 'optional' => true, 'hint' => 'Deixe em branco para manter a atual.', 'attrs' => 'autocomplete="new-password" minlength="8"']) ?>
                <?= view('field', ['name' => 'password_confirmation', 'label' => 'Confirmar nova senha', 'type' => 'password', 'attrs' => 'autocomplete="new-password"']) ?>
                <?= view('field', ['name' => 'current_password', 'label' => 'Senha atual', 'type' => 'password', 'span' => true, 'hint' => 'Obrigatória para salvar qualquer alteração de acesso.', 'attrs' => 'required autocomplete="current-password"']) ?>
            </div>
            <div class="form-actions"><button class="btn btn-ink" type="submit">Atualizar acesso</button></div>
        </form>
    </div>

    <div class="stack">
        <section class="card card-pad">
            <h2 class="panel-title">Foto</h2>
            <div class="row" style="margin-bottom:1rem">
                <?php if ($p['avatar_path']): ?>
                    <img class="avatar avatar-lg" src="<?= e(media($p['avatar_path'])) ?>" alt="Sua foto atual">
                <?php else: ?>
                    <span class="avatar avatar-lg" aria-hidden="true"><?= e(initials($p['display_name'])) ?></span>
                <?php endif; ?>
            </div>
            <form method="post" action="<?= e(url($area . '/perfil/foto')) ?>" enctype="multipart/form-data" class="stack-sm">
                <?= csrf_field() ?>
                <label class="field"><span>Nova foto</span><input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required></label>
                <small class="hint">JPG, PNG ou WebP de até <?= (int) config('uploads.image_max_mb', 5) ?> MB. Prefira uma foto de rosto com boa luz.</small>
                <div><button class="btn btn-ghost btn-sm" type="submit">Enviar foto</button></div>
            </form>
        </section>

        <?php if ($isCreator): ?>
            <section class="card card-pad">
                <h2 class="panel-title">Selo de verificado</h2>
                <?php if ((int) $creator['is_verified'] === 1): ?>
                    <p class="mb-0"><span class="badge badge-verified">Verificado</span> desde <?= e(fmt_date($creator['verified_at'])) ?>.</p>
                <?php elseif ($creator['verification_requested_at']): ?>
                    <p class="muted mb-0">Pedido enviado em <?= e(fmt_date($creator['verification_requested_at'])) ?>. A equipe confere identidade e histórico antes de conceder o selo.</p>
                <?php else: ?>
                    <p class="muted small">O selo indica que a equipe conferiu sua identidade. Perfis verificados aparecem primeiro na busca.</p>
                    <form method="post" action="<?= e(url('/painel/perfil/verificacao')) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" type="submit">Pedir verificação</button></form>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</div>
