<section class="dash-page">
    <div class="section-head">
        <div>
            <h1>Painel master</h1>
            <p class="muted">Visão da operação, conteúdo e financeiro da plataforma.</p>
        </div>
        <a class="btn btn-accent" href="<?= e(url('/admin/configuracoes')) ?>">Configurações</a>
    </div>

    <div class="admin-shortcuts">
        <a href="<?= e(url('/admin/usuarios')) ?>">Usuários</a>
        <a href="<?= e(url('/admin/servicos')) ?>">Moderar serviços</a>
        <a href="<?= e(url('/admin/pedidos')) ?>">Pedidos</a>
        <a href="<?= e(url('/admin/saques')) ?>">Saques</a>
        <a href="<?= e(url('/admin/categorias')) ?>">Categorias</a>
        <a href="<?= e(url('/admin/paginas')) ?>">Páginas</a>
    </div>

    <div class="stat-grid stats-admin">
        <?php
        $labels = [
            'users' => 'Usuários',
            'freelancers' => 'Freelancers',
            'clients' => 'Clientes',
            'services' => 'Serviços',
            'published_services' => 'Publicados',
            'pending_services' => 'Pendentes',
            'orders' => 'Pedidos',
            'orders_completed' => 'Concluídos',
            'orders_cancelled' => 'Cancelados',
            'projects' => 'Projetos',
            'proposals' => 'Propostas',
            'withdrawals_pending' => 'Saques pendentes',
        ];
        foreach ($labels as $key => $label):
        ?>
            <div><span><?= e($label) ?></span><strong><?= (int) ($stats[$key] ?? 0) ?></strong></div>
        <?php endforeach; ?>
    </div>

    <div class="stat-grid stats-admin" style="margin-top:1rem">
        <div><span>Volume (pedidos)</span><strong><?= e(money((int) ($finance['gmv'] ?? 0))) ?></strong></div>
        <div><span>Taxas da plataforma</span><strong><?= e(money((int) ($finance['fees'] ?? 0))) ?></strong></div>
        <div><span>Saques em análise</span><strong><?= e(money((int) ($finance['pending_withdrawals'] ?? 0))) ?></strong></div>
        <div><span>Saques pagos</span><strong><?= e(money((int) ($finance['paid_withdrawals'] ?? 0))) ?></strong></div>
    </div>

    <div class="admin-split">
        <div class="panel">
            <h2>Novas contas</h2>
            <?php if (!$recentUsers): ?>
                <p class="muted">Nenhum cadastro recente.</p>
            <?php else: ?>
                <ul class="admin-list">
                    <?php foreach ($recentUsers as $row): ?>
                        <li>
                            <a href="<?= e(url('/admin/usuarios/' . $row['id'] . '/editar')) ?>"><?= e($row['display_name'] ?: $row['email']) ?></a>
                            <small><?= e($row['email']) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="panel">
            <h2>Pedidos recentes</h2>
            <?php if (!$recentOrders): ?>
                <p class="muted">Ainda não há pedidos.</p>
            <?php else: ?>
                <ul class="admin-list">
                    <?php foreach ($recentOrders as $row): ?>
                        <li>
                            <a href="<?= e(url('/admin/pedidos/' . $row['id'])) ?>"><?= e($row['public_code']) ?></a>
                            <small><?= e($row['status']) ?> · <?= e(money((int) $row['total_cents'])) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>
