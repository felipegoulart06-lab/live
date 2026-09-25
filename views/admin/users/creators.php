<div class="page-title">
    <div><h1>Criadores</h1><p>Perfis, anúncios ativos, reputação e verificação.</p></div>
</div>
<?= view('user-filters', ['action' => '/admin/criadores', 'creators' => true]) ?>
<?php if ($result['rows'] === []): ?>
    <?= view('empty', ['title' => 'Nenhum criador encontrado', 'action' => 'Limpar filtros', 'actionUrl' => url('/admin/criadores')]) ?>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Criador</th><th scope="col">Cidade</th><th scope="col" class="num">Anúncios ativos</th><th scope="col" class="num">Contratos</th><th scope="col" class="num">Nota</th><th scope="col">Status</th><th scope="col">Cadastro</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $u): ?>
                <tr>
                    <td>
                        <div class="person">
                            <?php if ($u['avatar_path']): ?><img class="avatar avatar-sm" src="<?= e(media($u['avatar_path'])) ?>" alt=""><?php else: ?><span class="avatar avatar-sm" aria-hidden="true"><?= e(initials($u['display_name'])) ?></span><?php endif; ?>
                            <div>
                                <a href="<?= e(url('/admin/criadores/' . $u['uuid'])) ?>"><strong><?= e($u['display_name']) ?></strong></a>
                                <small><?= e($u['email']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td><?= e(trim(($u['city'] ?? '') . ($u['state'] ? '/' . $u['state'] : ''), '/') ?: '—') ?></td>
                    <td class="num"><?= (int) $u['active_listings'] ?></td>
                    <td class="num"><?= (int) $u['contracts_count'] ?></td>
                    <td class="num"><?= (int) $u['rating_count'] > 0 ? number_format((float) $u['rating_avg'], 1, ',', '') : '—' ?></td>
                    <td>
                        <?= status_badge('user', $u['status']) ?>
                        <?php if ((int) $u['is_verified'] === 1): ?><span class="badge badge-verified">Verificado</span><?php elseif ($u['verification_requested_at']): ?><span class="status status--info">Pediu verificação</span><?php endif; ?>
                    </td>
                    <td class="nowrap"><?= e(fmt_date($u['created_at'])) ?><small><?= is_online($u['last_seen_at']) ? 'Online agora' : 'Visto ' . e(time_ago($u['last_seen_at'])) ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= view('pager', ['result' => $result]) ?>
<?php endif; ?>
