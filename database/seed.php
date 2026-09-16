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
    'service_faqs', 'service_images', 'service_extras', 'service_packages', 'services',
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
    ['name' => 'Freelancer', 'slug' => 'freelancer', 'description' => 'Vende serviços e envia propostas', 'created_at' => $now, 'updated_at' => $now],
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
    'design-grafico' => ['Design Gráfico', 'Identidade, peças e produto visual.', [
        'Arte para redes sociais', 'Criação de logo', 'Identidade visual', 'Banners', 'Cartões', 'Ilustração', 'UX Design', 'Web Design', 'Apresentações', 'Embalagem',
    ]],
    'programacao' => ['Programação', 'Sistemas, sites e automações.', [
        'Websites', 'Landing Pages', 'PHP', 'WordPress', 'APIs', 'Sistemas', 'CRM', 'ERP', 'Automação', 'Integrações', 'Banco de dados', 'Aplicativos', 'Chatbots', 'Inteligência Artificial',
    ]],
    'marketing' => ['Marketing', 'Aquisição, conteúdo e mídia.', [
        'Marketing Digital', 'Google Ads', 'Meta Ads', 'SEO', 'Social Media', 'E-mail Marketing', 'Copywriting', 'Geração de Leads', 'Tráfego pago',
    ]],
    'video' => ['Vídeo', 'Edição, motion e conteúdo em movimento.', [
        'Edição', 'UGC', 'Vídeos comerciais', 'Animação', 'Motion', 'YouTube', 'Shorts', 'Reels',
    ]],
    'audio-musica' => ['Áudio e Música', 'Trilhas, locução e mixagem.', [
        'Locução', 'Trilha sonora', 'Mixagem', 'Jingles', 'Podcast',
    ]],
    'redacao' => ['Redação e Tradução', 'Texto com clareza e propósito.', [
        'Redação', 'Tradução', 'Revisão', 'Roteiro', 'Artigos',
    ]],
    'negocios' => ['Negócios e Consultoria', 'Estratégia e operação.', [
        'Consultoria', 'Negócios', 'Contabilidade', 'Serviços administrativos', 'Assistência virtual', 'Educação',
    ]],
    'arquitetura' => ['Arquitetura e Engenharia', 'Projeto, obra e espaços.', [
        'Arquitetura', 'Engenharia', 'Interiores', 'Plantas', 'Render',
    ]],
    'dados' => ['Dados e IA', 'Análise, modelos e automação inteligente.', [
        'Dados', 'Inteligência Artificial', 'Automação com IA', 'Dashboards', 'Planilhas avançadas',
    ]],
    'ecommerce' => ['E-commerce e Tecnologia', 'Lojas, CRM e operação digital.', [
        'E-commerce', 'Tecnologia', 'CRM', 'ERP', 'Integrações', 'Hospedagem',
    ]],
    'fotografia' => ['Fotografia', 'Imagem para marca e produto.', [
        'Ensaio', 'Produto', 'Eventos', 'Edição de fotos',
    ]],
];

$categoryImages = [
    'design-grafico' => 'images/cat-design.jpg',
    'programacao' => 'images/cat-code.jpg',
    'marketing' => 'images/cat-marketing.jpg',
    'video' => 'images/cat-video.jpg',
    'audio-musica' => 'images/cat-audio.jpg',
    'redacao' => 'images/cat-writing.jpg',
    'negocios' => 'images/cat-business.jpg',
    'arquitetura' => 'images/cat-arch.jpg',
    'dados' => 'images/cat-data.jpg',
    'ecommerce' => 'images/cat-shop.jpg',
    'fotografia' => 'images/cat-photo.jpg',
];

$sort = 1;
foreach ($catalog as $slug => [$name, $desc, $subs]) {
    $featured = $sort <= 6 ? 1 : 0;
    $image = $categoryImages[$slug] ?? null;
    $stmt = $pdo->prepare('INSERT INTO categories (name, slug, icon, image_path, short_description, is_active, is_featured, sort_order, meta_title, meta_description, created_at, updated_at) VALUES (?,?,?,?,?,1,?,?,?,?,?,?)');
    $stmt->execute([$name, $slug, 'grid', $image, $desc, $featured, $sort, $name . ' | Nexo', $desc, $now, $now]);
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
    'platform_name' => ['Nexo', 'general'],
    'tagline' => ['Profissionais certos, no ritmo do seu projeto.', 'general'],
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
    'meta_title' => ['Nexo — marketplace de profissionais', 'seo'],
    'meta_description' => ['Encontre e contrate profissionais de design, programação, marketing, vídeo e muito mais.', 'seo'],
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
    ['title' => 'Sobre', 'slug' => 'sobre', 'content' => '<p>O Nexo conecta empresas e profissionais em um fluxo de trabalho claro: briefing, entrega, revisão e pagamento com registro financeiro real.</p>', 'status' => 'published', 'sort_order' => 1, 'meta_title' => 'Sobre o Nexo', 'meta_description' => 'Como a plataforma funciona.', 'created_at' => $now, 'updated_at' => $now],
    ['title' => 'Como funciona', 'slug' => 'como-funciona', 'content' => '<p>Publique um projeto ou contrate um serviço pronto. Converse, alinhe o briefing e acompanhe cada etapa até a conclusão.</p>', 'status' => 'published', 'sort_order' => 2, 'meta_title' => 'Como funciona', 'meta_description' => 'Fluxo de contratação.', 'created_at' => $now, 'updated_at' => $now],
    ['title' => 'Termos', 'slug' => 'termos', 'content' => '<p>Estes termos regulam o uso da plataforma. Personalize este texto no painel administrativo.</p>', 'status' => 'published', 'sort_order' => 3, 'meta_title' => 'Termos de uso', 'meta_description' => 'Termos de uso da plataforma.', 'created_at' => $now, 'updated_at' => $now],
    ['title' => 'Privacidade', 'slug' => 'privacidade', 'content' => '<p>Descreva aqui a política de privacidade e retenção de dados.</p>', 'status' => 'published', 'sort_order' => 4, 'meta_title' => 'Privacidade', 'meta_description' => 'Política de privacidade.', 'created_at' => $now, 'updated_at' => $now],
    ['title' => 'Ajuda', 'slug' => 'ajuda', 'content' => '<p>Central de ajuda inicial. Artigos completos serão gerenciados no módulo de suporte.</p>', 'status' => 'published', 'sort_order' => 5, 'meta_title' => 'Ajuda', 'meta_description' => 'Central de ajuda.', 'created_at' => $now, 'updated_at' => $now],
    ['title' => 'Contato', 'slug' => 'contato', 'content' => '<p>Fale com o time pelo e-mail de suporte configurado no painel.</p>', 'status' => 'published', 'sort_order' => 6, 'meta_title' => 'Contato', 'meta_description' => 'Fale com o Nexo.', 'created_at' => $now, 'updated_at' => $now],
]);

insert($pdo, 'faqs', [
    ['question' => 'Como funciona o pagamento?', 'answer' => 'O valor fica registrado na carteira da plataforma até a entrega ser aprovada. Não há saldo fictício: cada movimento corresponde a um evento financeiro real.', 'placement' => 'both', 'is_active' => 1, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
    ['question' => 'Posso conversar antes de contratar?', 'answer' => 'Sim. Cliente e profissional podem abrir uma conversa, enviar arquivos e alinhar o escopo antes do pedido.', 'placement' => 'both', 'is_active' => 1, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
    ['question' => 'E se eu precisar de alterações?', 'answer' => 'Cada pacote define revisões incluídas. Solicitações extras ficam registradas na timeline do pedido.', 'placement' => 'both', 'is_active' => 1, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
    ['question' => 'Como me torno um profissional verificado?', 'answer' => 'Envie documentos no fluxo de verificação. A equipe analisa e, se aprovado, o selo passa a aparecer no perfil e nos serviços.', 'placement' => 'both', 'is_active' => 1, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
    ['question' => 'Qual a comissão da plataforma?', 'answer' => 'A comissão padrão é configurável. Pode variar por categoria, plano do profissional ou acordo específico.', 'placement' => 'both', 'is_active' => 1, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
]);

insert($pdo, 'banners', [[
    'placement' => 'home_hero',
    'title' => 'Encontre o profissional certo para o próximo passo.',
    'subtitle' => 'Briefing claro, prazos visíveis e um fluxo de entrega que a empresa e o freelancer acompanham juntos.',
    'cta_label' => 'Explorar serviços',
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
    ['author_name' => 'Marina Alves', 'author_role' => 'Diretora de marca', 'quote' => 'O fluxo de briefing e revisão reduziu o vai-e-volta que eu tinha em planilhas e e-mail.', 'rating' => 5, 'avatar_path' => 'images/avatar-marina.jpg', 'is_active' => 1, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
    ['author_name' => 'Rafael Moura', 'author_role' => 'Desenvolvedor', 'quote' => 'Consigo mostrar pacotes, adicionais e prazos sem improvisar proposta em PDF.', 'rating' => 5, 'avatar_path' => 'images/avatar-rafael.jpg', 'is_active' => 1, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
    ['author_name' => 'Helena Costa', 'author_role' => 'Fundadora', 'quote' => 'Publiquei o projeto e comparei propostas com critérios iguais. Contratei no mesmo dia.', 'rating' => 5, 'avatar_path' => 'images/avatar-helena.jpg', 'is_active' => 1, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
]);

insert($pdo, 'email_templates', [
    ['slug' => 'welcome', 'subject' => 'Bem-vindo ao Nexo', 'body' => 'Olá {{name}}, sua conta foi criada.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
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

$adminEmail = 'admin@nexo.test';
$adminPass = password_hash('NexoAdmin!234', PASSWORD_DEFAULT);
$uuid = sprintf('%s%s-%s-%s-%s-%s%s%s', ...str_split(bin2hex(random_bytes(16)), 4));
$stmt = $pdo->prepare('INSERT INTO users (uuid, email, password, account_type, status, email_verified_at, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)');
$stmt->execute([$uuid, $adminEmail, $adminPass, 'both', 'active', $now, $now, $now]);
$adminId = (int) $pdo->lastInsertId();
$pdo->prepare('INSERT INTO user_roles (user_id, role_id, created_at) VALUES (?,?,?)')->execute([$adminId, $roleIds['super_admin'], $now]);
$pdo->prepare('INSERT INTO profiles (user_id, display_name, professional_name, slug, headline, country, is_verified, created_at, updated_at) VALUES (?,?,?,?,?,?,1,?,?)')
    ->execute([$adminId, 'Equipe Nexo', 'Nexo', 'equipe-nexo', 'Operação da plataforma', 'BR', $now, $now]);
$pdo->prepare('INSERT INTO wallets (user_id, available_cents, pending_cents, reserved_cents, currency, created_at, updated_at) VALUES (?,0,0,0,?,?,?)')
    ->execute([$adminId, 'BRL', $now, $now]);

echo "Seed concluído. Admin: admin@nexo.test / NexoAdmin!234\n";
