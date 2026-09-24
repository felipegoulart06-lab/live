<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$pdo = \App\Core\Database::pdo();

$now = date('Y-m-d H:i:s');

function insert($pdo, string $table, array $rows): void
{
    if ($rows === []) {
        return;
    }
    $columns = array_keys($rows[0]);
    $colSql = implode(',', $columns);
    $placeholders = implode(',', array_map(static fn ($c) => ':' . $c, $columns));
    $sql = "INSERT INTO {$table} ({$colSql}) VALUES ({$placeholders})";
    $stmt = $pdo->prepare($sql);
    foreach ($rows as $row) {
        $stmt->execute($row);
    }
}

$pdo->exec('PRAGMA foreign_keys = OFF');
foreach ([
    'service_reviews', 'service_faqs', 'service_images', 'service_extras', 'service_packages', 'services', 'hire_intents',
    'user_badges', 'user_skills', 'user_languages', 'user_roles', 'role_permissions',
    'permissions', 'roles', 'settings', 'categories', 'subcategories',
    'languages', 'badges', 'skills', 'subscription_plans', 'pages', 'faqs', 'banners', 'testimonials',
    'email_templates', 'help_articles', 'help_categories', 'commission_rules', 'wallets', 'profiles', 'users',
    'login_logs', 'security_tokens', 'audit_logs',
] as $t) {
    $pdo->exec("DELETE FROM {$t}");
}
$pdo->exec('PRAGMA foreign_keys = ON');

insert($pdo, 'roles', [
    ['name' => 'Visitante', 'slug' => 'visitor', 'description' => 'Papel lógico de não autenticado', 'created_at' => $now, 'updated_at' => $now],
    ['name' => 'Cliente', 'slug' => 'client', 'description' => 'Compra serviços e publica projetos', 'created_at' => $now, 'updated_at' => $now],
    ['name' => 'Criador', 'slug' => 'freelancer', 'description' => 'Anuncia horas de vídeo para empresas', 'created_at' => $now, 'updated_at' => $now],
    ['name' => 'Moderador', 'slug' => 'moderator', 'description' => 'Modera conteúdo e disputas', 'created_at' => $now, 'updated_at' => $now],
    ['name' => 'Administrador', 'slug' => 'admin', 'description' => 'Opera o painel administrativo', 'created_at' => $now, 'updated_at' => $now],
    ['name' => 'Super Administrador', 'slug' => 'super_admin', 'description' => 'Acesso total da plataforma', 'created_at' => $now, 'updated_at' => $now],
]);

$permissions = [
    ['users.view', 'Ver usuários', 'users'],
    ['users.manage', 'Gerenciar usuários', 'users'],
    ['services.moderate', 'Moderar serviços', 'services'],
    ['orders.view', 'Ver pedidos', 'orders'],
    ['finance.manage', 'Gerenciar financeiro', 'finance'],
    ['cms.manage', 'Gerenciar CMS', 'cms'],
    ['settings.manage', 'Gerenciar configurações', 'settings'],
    ['disputes.manage', 'Gerenciar disputas', 'disputes'],
    ['reports.manage', 'Gerenciar denúncias', 'reports'],
    ['verifications.manage', 'Gerenciar verificações', 'verifications'],
];

$permRows = [];
foreach ($permissions as [$slug, $name, $group]) {
    $permRows[] = ['name' => $name, 'slug' => $slug, 'group_name' => $group, 'created_at' => $now, 'updated_at' => $now];
}
insert($pdo, 'permissions', $permRows);

$roleIds = [];
foreach ($pdo->query('SELECT id, slug FROM roles') as $r) {
    $roleIds[$r['slug']] = (int) $r['id'];
}
$permIds = [];
foreach ($pdo->query('SELECT id, slug FROM permissions') as $p) {
    $permIds[$p['slug']] = (int) $p['id'];
}

$assign = [
    'moderator' => ['services.moderate', 'disputes.manage', 'reports.manage', 'verifications.manage'],
    'admin' => array_column($permissions, 0),
    'super_admin' => array_column($permissions, 0),
];
$rp = [];
foreach ($assign as $role => $slugs) {
    foreach ($slugs as $slug) {
        $rp[] = ['role_id' => $roleIds[$role], 'permission_id' => $permIds[$slug]];
    }
}
insert($pdo, 'role_permissions', $rp);

$catalog = [
    'institucional' => ['Vídeo institucional', 'A empresa no tom certo: apresentação, cultura e posicionamento.', [
        'Institucional', 'Cultura', 'Onboarding', 'Marca empregadora', 'Bastidores da operação',
    ]],
    'produto' => ['Vídeo de produto', 'Mostre o que a empresa vende, no ritmo de quem vai comprar.', [
        'Produto', 'Serviço', 'Unboxing', 'Demonstração', 'Antes e depois',
    ]],
    'treinamento' => ['Treinamento interno', 'Horas de vídeo para o time: processo, produto e atendimento.', [
        'Treinamento', 'Processos', 'Atendimento', 'Segurança', 'Onboarding de equipe',
    ]],
    'comercial' => ['Comercial e anúncio', 'Peças para site, TV interna e mídia paga.', [
        'Comercial', 'Anúncio', 'VSL', 'TV corporativa', 'Lançamento',
    ]],
    'ugc' => ['UGC e redes', 'Tom de conversa para a empresa usar nas redes, sem contato público.', [
        'UGC', 'Reels', 'Stories', 'Depoimento', 'Conversa em câmera',
    ]],
    'eventos' => ['Eventos e cobertura', 'Horas de captação em evento, loja ou fábrica.', [
        'Evento', 'Feira', 'Loja', 'Fábrica', 'Convenção',
    ]],
    'locucao' => ['Locução', 'Voz para o vídeo que a empresa já tem ou vai gravar.', [
        'Locução institucional', 'Narração de treino', 'Off para anúncio', 'Áudio para IVR',
    ]],
    'edicao' => ['Edição de vídeo', 'Corte, legenda e versão a partir do material da empresa.', [
        'Edição', 'Legendas', 'Corte vertical', 'Pacote de campanha',
    ]],
];

$categoryImages = [
    'institucional' => 'images/hero-studio.jpg',
    'produto' => 'images/cat-shop.jpg',
    'treinamento' => 'images/cat-business.jpg',
    'comercial' => 'images/cat-marketing.jpg',
    'ugc' => 'images/cat-video.jpg',
    'eventos' => 'images/cat-photo.jpg',
    'locucao' => 'images/cat-audio.jpg',
    'edicao' => 'images/svc-edit.jpg',
];

$sort = 1;
foreach ($catalog as $slug => [$name, $desc, $subs]) {
    $featured = $sort <= 6 ? 1 : 0;
    $image = $categoryImages[$slug] ?? null;
    $stmt = $pdo->prepare('INSERT INTO categories (name, slug, icon, image_path, short_description, is_active, is_featured, sort_order, meta_title, meta_description, created_at, updated_at) VALUES (?,?,?,?,?,1,?,?,?,?,?,?)');
    $stmt->execute([$name, $slug, 'grid', $image, $desc, $featured, $sort, $name . ' | CinquentaConto', $desc, $now, $now]);
    $catId = (int) $pdo->lastInsertId();
    $ss = 1;
    foreach ($subs as $subName) {
        $subSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $subName) ?: $subName));
        $subSlug = trim($subSlug, '-');
        $s = $pdo->prepare('INSERT INTO subcategories (category_id, name, slug, is_active, sort_order, created_at, updated_at) VALUES (?,?,?,1,?,?,?)');
        $s->execute([$catId, $subName, $subSlug, $ss, $now, $now]);
        $ss++;
    }
    $sort++;
}

insert($pdo, 'languages', [
    ['name' => 'Português', 'code' => 'pt'],
    ['name' => 'Inglês', 'code' => 'en'],
    ['name' => 'Espanhol', 'code' => 'es'],
]);

insert($pdo, 'badges', [
    ['name' => 'Novo vendedor', 'slug' => 'new-seller', 'description' => 'Acabou de começar na plataforma', 'icon' => 'spark', 'created_at' => $now],
    ['name' => 'Vendedor verificado', 'slug' => 'verified', 'description' => 'Identidade conferida', 'icon' => 'shield', 'created_at' => $now],
    ['name' => 'Top Freelancer', 'slug' => 'top-freelancer', 'description' => 'Desempenho consistente', 'icon' => 'star', 'created_at' => $now],
    ['name' => 'Entrega rápida', 'slug' => 'fast-delivery', 'description' => 'Prazos curtos com regularidade', 'icon' => 'bolt', 'created_at' => $now],
    ['name' => '100 vendas', 'slug' => 'sales-100', 'description' => '100 pedidos concluídos', 'icon' => '100', 'created_at' => $now],
    ['name' => '500 vendas', 'slug' => 'sales-500', 'description' => '500 pedidos concluídos', 'icon' => '500', 'created_at' => $now],
    ['name' => '1.000 vendas', 'slug' => 'sales-1000', 'description' => '1.000 pedidos concluídos', 'icon' => '1000', 'created_at' => $now],
    ['name' => 'Nota 5 estrelas', 'slug' => 'five-stars', 'description' => 'Avaliação média máxima', 'icon' => 'five', 'created_at' => $now],
]);

insert($pdo, 'subscription_plans', [
    ['name' => 'Grátis', 'slug' => 'free', 'price_cents' => 0, 'billing_interval' => 'month', 'max_services' => 3, 'max_images' => 5, 'max_portfolio' => 4, 'commission_percent' => 15.00, 'search_priority' => 0, 'featured_home' => 0, 'advanced_analytics' => 0, 'badge_slug' => null, 'is_active' => 1, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
    ['name' => 'Pro', 'slug' => 'pro', 'price_cents' => 4900, 'billing_interval' => 'month', 'max_services' => 15, 'max_images' => 12, 'max_portfolio' => 20, 'commission_percent' => 12.00, 'search_priority' => 1, 'featured_home' => 0, 'advanced_analytics' => 1, 'badge_slug' => 'pro', 'is_active' => 1, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
    ['name' => 'Premium', 'slug' => 'premium', 'price_cents' => 9900, 'billing_interval' => 'month', 'max_services' => null, 'max_images' => 30, 'max_portfolio' => 80, 'commission_percent' => 8.00, 'search_priority' => 2, 'featured_home' => 1, 'advanced_analytics' => 1, 'badge_slug' => 'premium', 'is_active' => 1, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
]);

$settings = [
    'platform_name' => ['CinquentaConto', 'general'],
    'tagline' => ['Horas de vídeo para a sua empresa. Tema definido por quem paga.', 'general'],
    'support_email' => ['suporte@localhost', 'general'],
    'support_phone' => ['', 'general'],
    'whatsapp' => ['', 'general'],
    'currency' => ['BRL', 'finance'],
    'locale' => ['pt_BR', 'general'],
    'commission_percent' => ['10', 'finance'],
    'fixed_fee_cents' => ['0', 'finance'],
    'withdraw_fee_cents' => ['0', 'finance'],
    'min_withdraw_cents' => ['5000', 'finance'],
    'upload_max_mb' => ['20', 'security'],
    'maintenance_mode' => ['0', 'general'],
    'registrations_open' => ['1', 'general'],
    'meta_title' => ['CinquentaConto — horas de vídeo para a sua empresa', 'seo'],
    'meta_description' => ['Empresas encontram criadores para gravar 2, 4, 6 ou 8 horas de vídeo. O tema é de quem paga. Telefone e WhatsApp não ficam expostos.', 'seo'],
    'social_instagram' => ['', 'social'],
    'social_linkedin' => ['', 'social'],
    'ga_id' => ['', 'integrations'],
    'gtm_id' => ['', 'integrations'],
    'meta_pixel' => ['', 'integrations'],
    'google_oauth_enabled' => ['0', 'auth'],
    'google_client_id' => ['', 'auth'],
    'google_client_secret' => ['', 'auth'],
];
foreach ($settings as $key => [$value, $group]) {
    $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value, setting_group, created_at, updated_at) VALUES (?,?,?,?,?)');
    $stmt->execute([$key, $value, $group, $now, $now]);
}

insert($pdo, 'commission_rules', [[
    'scope' => 'global', 'category_id' => null, 'user_id' => null, 'percent' => 10.00, 'fixed_cents' => 0, 'withdraw_fee_cents' => 0, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
]]);

insert($pdo, 'pages', [
    ['title' => 'Sobre', 'slug' => 'sobre', 'content' => '<p>A CinquentaConto é o espaço em que empresas encontram pessoas para gravar vídeo sob demanda: 2, 4, 6 ou 8 horas. Quem paga define o tema. Telefone, e-mail e WhatsApp do criador não entram no anúncio público.</p>', 'status' => 'published', 'sort_order' => 1, 'meta_title' => 'Sobre a CinquentaConto', 'meta_description' => 'Horas de vídeo para empresas, sem contato exposto.', 'created_at' => $now, 'updated_at' => $now],
    ['title' => 'Como funciona', 'slug' => 'como-funciona', 'content' => '<p>A empresa escolhe o criador, compra um bloco de horas e descreve o tema. O criador entrega o vídeo combinado. Mensagens, briefing e arquivos ficam na plataforma — o anúncio não publica telefone.</p>', 'status' => 'published', 'sort_order' => 2, 'meta_title' => 'Como funciona', 'meta_description' => 'Contrate horas de vídeo com o tema da sua empresa.', 'created_at' => $now, 'updated_at' => $now],
    ['title' => 'Termos', 'slug' => 'termos', 'content' => '<p>Estes termos regulam o uso da plataforma. Personalize este texto no painel administrativo.</p>', 'status' => 'published', 'sort_order' => 3, 'meta_title' => 'Termos de uso', 'meta_description' => 'Termos de uso da plataforma.', 'created_at' => $now, 'updated_at' => $now],
    ['title' => 'Privacidade', 'slug' => 'privacidade', 'content' => '<p>O perfil público do criador não exibe telefone, e-mail pessoal nem WhatsApp. O contato comercial acontece dentro da plataforma após o login. Dados de pagamento e briefing ficam restritos ao pedido.</p>', 'status' => 'published', 'sort_order' => 4, 'meta_title' => 'Privacidade', 'meta_description' => 'Contato do criador não é público.', 'created_at' => $now, 'updated_at' => $now],
    ['title' => 'Ajuda', 'slug' => 'ajuda', 'content' => '<p>Central de ajuda inicial. Artigos completos serão gerenciados no módulo de suporte.</p>', 'status' => 'published', 'sort_order' => 5, 'meta_title' => 'Ajuda', 'meta_description' => 'Central de ajuda.', 'created_at' => $now, 'updated_at' => $now],
    ['title' => 'Contato', 'slug' => 'contato', 'content' => '<p>Fale com o time pelo canal de suporte da plataforma. Não use este espaço para pedir telefone de criadores.</p>', 'status' => 'published', 'sort_order' => 6, 'meta_title' => 'Contato', 'meta_description' => 'Fale com a CinquentaConto.', 'created_at' => $now, 'updated_at' => $now],
]);

insert($pdo, 'faqs', [
    ['question' => 'O que a empresa está comprando?', 'answer' => 'Blocos de 2, 4, 6 ou 8 horas de vídeo. O tema, o recado e o uso (treino, produto, institucional) são definidos por quem paga.', 'placement' => 'both', 'is_active' => 1, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
    ['question' => 'Vou ver o telefone do criador?', 'answer' => 'Não. Telefone, WhatsApp e e-mail pessoal não entram no anúncio. A conversa e o briefing ficam na plataforma.', 'placement' => 'both', 'is_active' => 1, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
    ['question' => 'Posso mudar o tema depois?', 'answer' => 'Sim, pelo pedido, enquanto as horas ainda não foram consumidas. Alterações grandes podem pedir um bloco extra de horas.', 'placement' => 'both', 'is_active' => 1, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
    ['question' => 'Como o criador anuncia?', 'answer' => 'Abre uma conta, descreve o estilo de vídeo e publica pacotes de horas. O perfil público mostra só nome curto, cidade-estado genérico e portfólio.', 'placement' => 'both', 'is_active' => 1, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
    ['question' => 'Qual a comissão da plataforma?', 'answer' => 'A comissão padrão é configurável no painel. Pode variar por categoria ou plano do criador.', 'placement' => 'both', 'is_active' => 1, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
]);

insert($pdo, 'banners', [[
    'placement' => 'home_hero',
    'title' => 'Encontre quem grava o vídeo da sua empresa',
    'subtitle' => 'Contrate 2, 4, 6 ou 8 horas de vídeo. O tema é definido por quem paga. Telefone e WhatsApp do criador não aparecem no anúncio.',
    'cta_label' => 'Ver criadores',
    'cta_url' => '/buscar',
    'image_path' => 'images/hero-studio.jpg',
    'starts_at' => null,
    'ends_at' => null,
    'is_active' => 1,
    'sort_order' => 1,
    'created_at' => $now,
    'updated_at' => $now,
]]);

insert($pdo, 'testimonials', [
    ['author_name' => 'Marina Alves', 'author_role' => 'Diretora de marca', 'quote' => 'Comprei 6 horas e mandei o tema do lançamento. Sem precisar publicar telefone de ninguém.', 'rating' => 5, 'avatar_path' => 'images/avatar-marina.jpg', 'is_active' => 1, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
    ['author_name' => 'Rafael Moura', 'author_role' => 'Criador de vídeo', 'quote' => 'Anuncio blocos de horas. A empresa define o assunto; eu gravo. O contato fica na plataforma.', 'rating' => 5, 'avatar_path' => 'images/avatar-rafael.jpg', 'is_active' => 1, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
    ['author_name' => 'Helena Costa', 'author_role' => 'Fundadora', 'quote' => 'Usei 4 horas para treinar o time. Escrevi o roteiro interno e recebi o vídeo sem sair da CinquentaConto.', 'rating' => 5, 'avatar_path' => 'images/avatar-helena.jpg', 'is_active' => 1, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
]);

insert($pdo, 'email_templates', [
    ['slug' => 'welcome', 'subject' => 'Bem-vindo à CinquentaConto', 'body' => 'Olá {{name}}, sua conta foi criada.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
    ['slug' => 'email_verification', 'subject' => 'Confirme seu e-mail', 'body' => 'Use o link de confirmação enviado pela plataforma.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
    ['slug' => 'password_reset', 'subject' => 'Redefinição de senha', 'body' => 'Utilize o token de redefinição para criar uma nova senha.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
    ['slug' => 'order_received', 'subject' => 'Novo pedido recebido', 'body' => 'Você recebeu um novo pedido.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
    ['slug' => 'payment_approved', 'subject' => 'Pagamento aprovado', 'body' => 'O pagamento do pedido foi aprovado.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
    ['slug' => 'delivery', 'subject' => 'Entrega enviada', 'body' => 'Uma entrega foi enviada no seu pedido.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
    ['slug' => 'message', 'subject' => 'Nova mensagem', 'body' => 'Você recebeu uma nova mensagem.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
    ['slug' => 'review', 'subject' => 'Nova avaliação', 'body' => 'Você recebeu uma avaliação.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
    ['slug' => 'withdrawal', 'subject' => 'Atualização de saque', 'body' => 'Houve uma atualização no seu saque.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
]);

insert($pdo, 'help_categories', [
    ['name' => 'Primeiros passos', 'slug' => 'primeiros-passos', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
    ['name' => 'Pedidos', 'slug' => 'pedidos', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
    ['name' => 'Pagamentos', 'slug' => 'pagamentos', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
]);
$helpCat = (int) $pdo->query("SELECT id FROM help_categories WHERE slug='primeiros-passos'")->fetchColumn();
insert($pdo, 'help_articles', [
    ['category_id' => $helpCat, 'title' => 'Como criar sua conta', 'slug' => 'como-criar-sua-conta', 'body' => '<p>Use e-mail válido, senha forte e escolha se deseja contratar ou vender.</p>', 'is_published' => 1, 'created_at' => $now, 'updated_at' => $now],
]);

$adminEmail = 'admin@cinquentaconto.test';
$adminPass = password_hash('CinquentaAdmin!234', PASSWORD_DEFAULT);
$uuid = sprintf('%s%s-%s-%s-%s-%s%s%s', ...str_split(bin2hex(random_bytes(16)), 4));
$stmt = $pdo->prepare('INSERT INTO users (uuid, email, password, account_type, status, email_verified_at, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)');
$stmt->execute([$uuid, $adminEmail, $adminPass, 'both', 'active', $now, $now, $now]);
$adminId = (int) $pdo->lastInsertId();
$pdo->prepare('INSERT INTO user_roles (user_id, role_id, created_at) VALUES (?,?,?)')->execute([$adminId, $roleIds['super_admin'], $now]);
$pdo->prepare('INSERT INTO profiles (user_id, display_name, professional_name, slug, headline, country, is_verified, created_at, updated_at) VALUES (?,?,?,?,?,?,1,?,?)')
    ->execute([$adminId, 'Equipe CinquentaConto', 'CinquentaConto', 'equipe-cinquentaconto', 'Operação da plataforma', 'BR', $now, $now]);
$pdo->prepare('INSERT INTO wallets (user_id, available_cents, pending_cents, reserved_cents, currency, created_at, updated_at) VALUES (?,0,0,0,?,?,?)')
    ->execute([$adminId, 'BRL', $now, $now]);

echo "Seed concluído. Admin: admin@cinquentaconto.test / CinquentaAdmin!234\n";
