<?php
use App\Services\Deals;

/**
 * Shared contract details for participants and admins.
 * @var array<string, mixed> $contract @var array<int, array<string, mixed>> $addons, $events, $attachments
 * @var array<string, mixed>|null $payment @var string $viewer creator|company|admin
 */
$c = $contract;
if ($viewer !== 'admin') {
    $events = array_values(array_filter($events, static fn (array $ev): bool => $ev['type'] !== 'note'));
}
?>
<section class="card card-pad">
    <h2 class="panel-title">Detalhes</h2>
    <dl class="kv">
        <dt>Anúncio</dt><dd><a href="<?= e(listing_url($c['listing_slug'])) ?>"><?= e($c['listing_title']) ?></a></dd>
        <dt>Empresa</dt><dd><?= e($c['company_name']) ?> <span class="muted">(<?= e($c['company_contact']) ?>)</span></dd>
        <dt>Criador</dt><dd><?= e($c['creator_name']) ?></dd>
        <dt>Horas</dt><dd><?= (int) $c['hours'] ?>h</dd>
        <dt>Tema</dt><dd><?= e($c['theme']) ?></dd>
        <dt>Gravação</dt><dd><?= $c['scheduled_date'] ? e(fmt_date($c['scheduled_date'])) . ($c['scheduled_time'] ? ' às ' . e(substr($c['scheduled_time'], 0, 5)) : '') : 'A combinar' ?></dd>
        <dt>Solicitação</dt><dd><?= e($c['request_code']) ?></dd>
    </dl>
    <?php if ($c['briefing']): ?>
        <h3 style="margin-top:1rem">Briefing</h3>
        <div class="pre"><?= e($c['briefing']) ?></div>
    <?php endif; ?>
    <?php if ($c['status'] === 'cancelled' && $c['cancel_reason']): ?>
        <div class="reason-box" style="margin-top:1rem"><strong>Motivo do cancelamento</strong><p class="mb-0 pre"><?= e($c['cancel_reason']) ?></p></div>
    <?php endif; ?>
</section>

<section class="card card-pad">
    <h2 class="panel-title">Linha do tempo</h2>
    <?php if ($events === []): ?>
        <p class="muted mb-0">Sem eventos registrados.</p>
    <?php else: ?>
        <ol class="timeline">
            <?php foreach ($events as $ev): ?>
                <li>
                    <strong><?= e(Deals::EVENT_LABELS[$ev['type']] ?? $ev['type']) ?></strong>
                    <small><?= e(fmt_datetime($ev['created_at'])) ?><?= $ev['actor_name'] ? ' · ' . e($ev['actor_role'] === 'admin' ? 'Equipe ' . brand_name() : $ev['actor_name']) : '' ?></small>
                    <?php if ($ev['note']): ?><p class="pre"><?= e($ev['note']) ?></p><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>

<section class="card card-pad" id="arquivos">
    <h2 class="panel-title">Arquivos</h2>
    <?php if ($attachments === []): ?>
        <p class="muted small">Nenhum arquivo anexado. Roteiros, logotipos e referências ficam aqui, visíveis só para os participantes.</p>
    <?php else: ?>
        <ul class="list" style="margin-bottom:1rem">
            <?php foreach ($attachments as $file): ?>
                <li>
                    <a href="<?= e(url('/arquivos/' . $file['uuid'])) ?>"><strong><?= e($file['original_name']) ?></strong><small><?= e($file['owner_name']) ?> · <?= e(fmt_datetime($file['created_at'])) ?> · <?= number_format($file['size_bytes'] / 1024, 0, ',', '.') ?> KB</small></a>
                    <a class="btn btn-ghost btn-sm" href="<?= e(url('/arquivos/' . $file['uuid'])) ?>">Baixar</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ($viewer !== 'admin' && $c['status'] !== 'cancelled'): ?>
        <form class="dropzone" method="post" action="<?= e(url(($viewer === 'creator' ? '/painel' : '/empresa') . '/contratos/' . $c['uuid'] . '/arquivos')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <label class="field"><span>Anexar arquivo</span><input type="file" name="file" accept=".pdf,.txt,.docx,.jpg,.jpeg,.png,.webp" required></label>
            <small class="hint">PDF, TXT, DOCX ou imagem, até <?= (int) config('uploads.file_max_mb', 10) ?> MB.</small>
            <div><button class="btn btn-ghost btn-sm" type="submit">Enviar arquivo</button></div>
        </form>
    <?php endif; ?>
</section>
