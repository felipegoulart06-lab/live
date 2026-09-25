<?php
/** @var string $content @var \App\Models\User $authUser */
use App\Core\Db;
use App\Services\Messages;
use App\Services\Notifier;

if ($authUser === null) {
    throw new RuntimeException('Painel sem usuário autenticado.');
}
$unreadNotifications = Notifier::unreadCount($authUser->id);
$groups = [];
if ($authUser->isAdmin()) {
    $pendingListings = (int) Db::value("SELECT COUNT(*) FROM listings WHERE status = 'pending' AND deleted_at IS NULL");
    $openReports = (int) Db::value("SELECT COUNT(*) FROM reports WHERE status IN ('open', 'reviewing')");
    $awaitingPayment = (int) Db::value("SELECT COUNT(*) FROM contracts WHERE status = 'awaiting_payment'");
    $roleLabel = $authUser->isMaster() ? 'Administrador master' : 'Administrador';
    $groups = [
        '' => [['/admin', 'Visão geral', 0]],
        'Anúncios' => [
            ['/admin/anuncios', 'Todos', 0],
            ['/admin/anuncios/pendentes', 'Pendentes de aprovação', $pendingListings],
            ['/admin/anuncios?status=active', 'Ativos', 0],
            ['/admin/anuncios?status=paused', 'Pausados', 0],
            ['/admin/anuncios?status=rejected', 'Reprovados', 0],
        ],
        'Usuários' => [
            ['/admin/criadores', 'Criadores', 0],
            ['/admin/empresas', 'Empresas', 0],
            ['/admin/bloqueados', 'Bloqueados', 0],
        ],
        'Negócios' => [
            ['/admin/solicitacoes', 'Solicitações', 0],
            ['/admin/contratos', 'Contratos', $awaitingPayment],
            ['/admin/pagamentos', 'Pagamentos', 0],
        ],
        'Comunicação' => [
            ['/admin/mensagens', 'Mensagens', 0],
            ['/admin/notificacoes', 'Notificações', 0],
            ['/admin/denuncias', 'Denúncias', $openReports],
            ['/admin/avaliacoes', 'Avaliações', 0],
        ],
        'Conteúdo' => [
            ['/admin/categorias', 'Categorias', 0],
            ['/admin/destaques', 'Destaques', 0],
            ['/admin/faq', 'FAQ', 0],
            ['/admin/conteudo', 'Página inicial', 0],
        ],
        'Plataforma' => [
            ['/admin/configuracoes', 'Configurações', 0],
            ['/admin/paginas?pagina=termos', 'Termos', 0],
            ['/admin/paginas?pagina=privacidade', 'Privacidade', 0],
            ['/admin/paginas', 'Todas as páginas', 0],
        ],
        'Administração' => [
            ['/admin/atividade', 'Atividade', 0],
            ['/admin/administradores', 'Administradores', 0],
        ],
    ];
} elseif ($authUser->isCreator()) {
    $pendingRequests = (int) Db::value("SELECT COUNT(*) FROM requests WHERE creator_id = :u AND status = 'pending'", ['u' => $authUser->id]);
    $roleLabel = 'Criador';
    $groups = [
        '' => [['/painel', 'Início', 0]],
        'Meus anúncios' => [
            ['/painel/anuncios', 'Todos', 0],
            ['/painel/anuncios?status=active', 'Ativos', 0],
            ['/painel/anuncios?status=draft', 'Rascunhos', 0],
            ['/painel/anuncios/novo', 'Criar anúncio', 0],
        ],
        'Negócios' => [
            ['/painel/solicitacoes', 'Solicitações', $pendingRequests],
            ['/painel/contratos', 'Contratos', 0],
        ],
        'Conta' => [
            ['/painel/mensagens', 'Mensagens', Messages::unreadCount($authUser->id, 'creator')],
            ['/painel/financeiro', 'Financeiro', 0],
            ['/painel/perfil', 'Perfil', 0],
            ['/ajuda', 'Ajuda', 0],
        ],
    ];
} else {
    $roleLabel = 'Empresa';
    $groups = [
        '' => [['/empresa', 'Início', 0]],
        'Negócios' => [
            ['/empresa/solicitacoes', 'Solicitações', 0],
            ['/empresa/contratos', 'Contratos', 0],
        ],
        'Conta' => [
            ['/empresa/mensagens', 'Mensagens', Messages::unreadCount($authUser->id, 'company')],
            ['/empresa/favoritos', 'Favoritos', 0],
            ['/empresa/perfil', 'Perfil', 0],
        ],
    ];
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Painel') . ' · ' . brand_name()) ?></title>
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <?= view('favicon') ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,620&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="panel-body">
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<div class="shell">
    <aside class="side" id="menu-painel" aria-label="Menu do painel">
        <div class="side-head">
            <a class="brand" href="<?= e(url('/')) ?>">
                <img src="<?= e(asset('images/favicon-32.png')) ?>" alt="" width="24" height="24">
                <span><?= e(brand_name()) ?></span>
            </a>
            <span class="side-role"><?= e($roleLabel) ?></span>
        </div>
        <nav class="side-nav">
            <?php foreach ($groups as $label => $items): ?>
                <?php if ($label !== ''): ?><p class="side-label"><?= e($label) ?></p><?php endif; ?>
                <?php foreach ($items as [$path, $name, $count]): ?>
                    <?php $active = nav_href_active($path); ?>
                    <a href="<?= e(url($path)) ?>" class="<?= $active ? 'is-active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?>>
                        <span><?= e($name) ?></span>
                        <?php if ($count > 0): ?><span class="count" aria-label="<?= (int) $count ?> pendentes"><?= $count > 99 ? '99+' : (int) $count ?></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
        <div class="side-foot">
            <div class="side-user">
                <?php if ($authUser->avatarPath): ?>
                    <img class="avatar avatar-sm" src="<?= e(media($authUser->avatarPath)) ?>" alt="">
                <?php else: ?>
                    <span class="avatar avatar-sm" aria-hidden="true"><?= e(initials($authUser->displayName)) ?></span>
                <?php endif; ?>
                <div>
                    <strong><?= e($authUser->displayName) ?></strong>
                    <small><?= e($authUser->email) ?></small>
                </div>
            </div>
            <?php if ($authUser->isCreator() && $authUser->slug): ?>
                <a class="text-link small" href="<?= e(creator_url($authUser->slug)) ?>">Ver meu perfil público</a>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/sair')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-ghost btn-sm btn-block" type="submit">Sair</button>
            </form>
        </div>
    </aside>
    <div>
        <header class="topbar">
            <button class="icon-btn" type="button" data-side-toggle aria-controls="menu-painel" aria-expanded="false">Menu</button>
            <div class="topbar-actions">
                <?php if ($authUser->isCreator()): ?>
                    <a class="btn btn-accent btn-sm" href="<?= e(url('/painel/anuncios/novo')) ?>">+ Criar anúncio</a>
                <?php elseif ($authUser->isCompany()): ?>
                    <a class="btn btn-accent btn-sm" href="<?= e(url('/anuncios')) ?>">Encontrar criadores</a>
                <?php endif; ?>
                <a class="text-link small" href="<?= e(url('/anuncios')) ?>">Ver o site</a>
                <a class="bell" href="<?= e(url('/notificacoes')) ?>">
                    Notificações
                    <?php if ($unreadNotifications > 0): ?><span class="count"><?= $unreadNotifications > 99 ? '99+' : $unreadNotifications ?></span><?php endif; ?>
                </a>
            </div>
        </header>
        <main class="content" id="conteudo">
            <?= view('flash', ['success' => $success ?? null, 'error' => $error ?? null]) ?>
            <?= $content ?>
        </main>
    </div>
</div>
<div class="toast-region" id="toast-region" aria-live="polite"></div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
