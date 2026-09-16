<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$pdo = \App\Core\Database::pdo();

$now = date('Y-m-d H:i:s');

$images = [
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

$upd = $pdo->prepare('UPDATE categories SET image_path = :img WHERE slug = :slug');
foreach ($images as $slug => $img) {
    $upd->execute(['img' => $img, 'slug' => $slug]);
}

$pdo->exec("UPDATE banners SET image_path = 'images/hero-studio.jpg' WHERE placement = 'home_hero'");
$pdo->exec("UPDATE testimonials SET avatar_path = 'images/avatar-marina.jpg' WHERE author_name = 'Marina Alves'");
$pdo->exec("UPDATE testimonials SET avatar_path = 'images/avatar-rafael.jpg' WHERE author_name = 'Rafael Moura'");
$pdo->exec("UPDATE testimonials SET avatar_path = 'images/avatar-helena.jpg' WHERE author_name = 'Helena Costa'");

$roleId = (int) $pdo->query("SELECT id FROM roles WHERE slug = 'freelancer'")->fetchColumn();

$sellers = [
    ['email' => 'ana.freire@cinquentaconto.test', 'name' => 'Ana Freire', 'slug' => 'ana-freire', 'headline' => 'Identidade visual e marca', 'avatar' => 'images/avatar-ana.jpg', 'city' => 'São Paulo', 'state' => 'SP', 'verified' => 1, 'rating' => 5.0, 'reviews' => 48, 'orders' => 62],
    ['email' => 'lucas.nunes@cinquentaconto.test', 'name' => 'Lucas Nunes', 'slug' => 'lucas-nunes', 'headline' => 'PHP, WordPress e sistemas', 'avatar' => 'images/avatar-lucas.jpg', 'city' => 'Curitiba', 'state' => 'PR', 'verified' => 1, 'rating' => 4.9, 'reviews' => 31, 'orders' => 44],
    ['email' => 'rafael.moura@cinquentaconto.test', 'name' => 'Rafael Moura', 'slug' => 'rafael-moura', 'headline' => 'Edição e motion', 'avatar' => 'images/avatar-rafael.jpg', 'city' => 'Rio de Janeiro', 'state' => 'RJ', 'verified' => 0, 'rating' => 4.8, 'reviews' => 19, 'orders' => 22],
    ['email' => 'priscila.costa@cinquentaconto.test', 'name' => 'Priscila Costa', 'slug' => 'priscila-costa', 'headline' => 'Vídeo UGC', 'avatar' => 'images/avatar-marina.jpg', 'city' => 'Belo Horizonte', 'state' => 'MG', 'verified' => 1, 'rating' => 4.75, 'reviews' => 109, 'orders' => 109],
    ['email' => 'ian.ramos@cinquentaconto.test', 'name' => 'Ian Ramos', 'slug' => 'ian-ramos', 'headline' => 'VSL e vídeo rápido', 'avatar' => 'images/avatar-lucas.jpg', 'city' => 'Recife', 'state' => 'PE', 'verified' => 1, 'rating' => 4.95, 'reviews' => 164, 'orders' => 164],
    ['email' => 'jonathan.silva@cinquentaconto.test', 'name' => 'Jonathan Silva', 'slug' => 'jonathan-silva', 'headline' => 'Propaganda em vídeo', 'avatar' => 'images/avatar-rafael.jpg', 'city' => 'Fortaleza', 'state' => 'CE', 'verified' => 1, 'rating' => 5.0, 'reviews' => 158, 'orders' => 158],
    ['email' => 'jamerson.alves@cinquentaconto.test', 'name' => 'Jamerson Alves', 'slug' => 'jamerson-alves', 'headline' => 'Comercial para negócios', 'avatar' => 'images/avatar-lucas.jpg', 'city' => 'Salvador', 'state' => 'BA', 'verified' => 1, 'rating' => 5.0, 'reviews' => 161, 'orders' => 161],
    ['email' => 'andressa.vieira@cinquentaconto.test', 'name' => 'Andressa Vieira', 'slug' => 'andressa-vieira', 'headline' => 'Demo de apps', 'avatar' => 'images/avatar-helena.jpg', 'city' => 'Campinas', 'state' => 'SP', 'verified' => 1, 'rating' => 5.0, 'reviews' => 21, 'orders' => 21],
    ['email' => 'luan.rocha@cinquentaconto.test', 'name' => 'Luan Rocha', 'slug' => 'luan-rocha', 'headline' => 'Comercial empresarial', 'avatar' => 'images/avatar-rafael.jpg', 'city' => 'Porto Alegre', 'state' => 'RS', 'verified' => 1, 'rating' => 4.9, 'reviews' => 485, 'orders' => 485],
    ['email' => 'bruna.souza@cinquentaconto.test', 'name' => 'Bruna Souza', 'slug' => 'bruna-souza', 'headline' => 'Vídeo de produto', 'avatar' => 'images/avatar-ana.jpg', 'city' => 'Goiânia', 'state' => 'GO', 'verified' => 1, 'rating' => 5.0, 'reviews' => 195, 'orders' => 195],
    ['email' => 'igor.lima@cinquentaconto.test', 'name' => 'Igor Lima', 'slug' => 'igor-lima', 'headline' => 'Vídeo natural rápido', 'avatar' => 'images/avatar-lucas.jpg', 'city' => 'Manaus', 'state' => 'AM', 'verified' => 1, 'rating' => 4.95, 'reviews' => 2426, 'orders' => 2426],
    ['email' => 'eduarda.dias@cinquentaconto.test', 'name' => 'Eduarda Dias', 'slug' => 'eduarda-dias', 'headline' => 'Vídeo de serviço', 'avatar' => 'images/avatar-helena.jpg', 'city' => 'Vitória', 'state' => 'ES', 'verified' => 1, 'rating' => 5.0, 'reviews' => 56, 'orders' => 56],
    ['email' => 'leticia.campos@cinquentaconto.test', 'name' => 'Leticia Campos', 'slug' => 'leticia-campos', 'headline' => 'Vídeo com edição', 'avatar' => 'images/avatar-marina.jpg', 'city' => 'Natal', 'state' => 'RN', 'verified' => 1, 'rating' => 5.0, 'reviews' => 80, 'orders' => 80],
    ['email' => 'ricardo.santos@cinquentaconto.test', 'name' => 'Ricardo Santos', 'slug' => 'ricardo-santos', 'headline' => 'Locução e voz', 'avatar' => 'images/avatar-rafael.jpg', 'city' => 'Brasília', 'state' => 'DF', 'verified' => 1, 'rating' => 5.0, 'reviews' => 2238, 'orders' => 2238],
    ['email' => 'monique.ferreira@cinquentaconto.test', 'name' => 'Monique Ferreira', 'slug' => 'monique-ferreira', 'headline' => 'Voz e imagem', 'avatar' => 'images/avatar-ana.jpg', 'city' => 'Florianópolis', 'state' => 'SC', 'verified' => 1, 'rating' => 5.0, 'reviews' => 1624, 'orders' => 1624],
    ['email' => 'daniel.duarte@cinquentaconto.test', 'name' => 'Daniel Duarte', 'slug' => 'daniel-duarte', 'headline' => 'Sites de conversão', 'avatar' => 'images/avatar-lucas.jpg', 'city' => 'São Luís', 'state' => 'MA', 'verified' => 0, 'rating' => 0, 'reviews' => 0, 'orders' => 0],
    ['email' => 'camile.vaz@cinquentaconto.test', 'name' => 'Camile Vaz', 'slug' => 'camile-vaz', 'headline' => 'Digitação e convites', 'avatar' => 'images/avatar-helena.jpg', 'city' => 'João Pessoa', 'state' => 'PB', 'verified' => 0, 'rating' => 0, 'reviews' => 0, 'orders' => 0],
    ['email' => 'tiago.dias@cinquentaconto.test', 'name' => 'Tiago Dias', 'slug' => 'tiago-dias', 'headline' => 'Páginas e WhatsApp', 'avatar' => 'images/avatar-rafael.jpg', 'city' => 'Maceió', 'state' => 'AL', 'verified' => 0, 'rating' => 0, 'reviews' => 0, 'orders' => 0],
];

$findUser = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$insertUser = $pdo->prepare(
    'INSERT INTO users (uuid, email, password, account_type, status, email_verified_at, created_at, updated_at)
     VALUES (:uuid, :email, :password, :type, :status, :verified, :created, :updated)'
);
$insertRole = $pdo->prepare('INSERT OR IGNORE INTO user_roles (user_id, role_id, created_at) VALUES (:u, :r, :c)');
$insertProfile = $pdo->prepare(
    'INSERT INTO profiles (user_id, display_name, professional_name, slug, headline, city, state, country, avatar_path, is_verified, rating_avg, rating_count, orders_completed, created_at, updated_at)
     VALUES (:user_id, :display_name, :professional_name, :slug, :headline, :city, :state, :country, :avatar_path, :is_verified, :rating_avg, :rating_count, :orders_completed, :created_at, :updated_at)'
);
$insertWallet = $pdo->prepare(
    'INSERT INTO wallets (user_id, available_cents, pending_cents, reserved_cents, currency, created_at, updated_at)
     VALUES (:user_id, 0, 0, 0, :currency, :created_at, :updated_at)'
);

$sellerIds = [];
$hash = password_hash('CinquentaDemo!234', PASSWORD_DEFAULT);

foreach ($sellers as $seller) {
    $findUser->execute(['email' => $seller['email']]);
    $id = $findUser->fetchColumn();
    if ($id) {
        $sellerIds[$seller['slug']] = (int) $id;
        $pdo->prepare('UPDATE profiles SET avatar_path = :a, headline = :h, is_verified = :v, rating_avg = :r, rating_count = :c, orders_completed = :o WHERE user_id = :id')
            ->execute([
                'a' => $seller['avatar'],
                'h' => $seller['headline'],
                'v' => $seller['verified'],
                'r' => $seller['rating'],
                'c' => $seller['reviews'],
                'o' => $seller['orders'],
                'id' => $id,
            ]);
        continue;
    }

    $uuid = sprintf('%s%s-%s-%s-%s-%s%s%s', ...str_split(bin2hex(random_bytes(16)), 4));
    $insertUser->execute([
        'uuid' => $uuid,
        'email' => $seller['email'],
        'password' => $hash,
        'type' => 'seller',
        'status' => 'active',
        'verified' => $now,
        'created' => $now,
        'updated' => $now,
    ]);
    $id = (int) $pdo->lastInsertId();
    $insertRole->execute(['u' => $id, 'r' => $roleId, 'c' => $now]);
    $insertProfile->execute([
        'user_id' => $id,
        'display_name' => $seller['name'],
        'professional_name' => $seller['name'],
        'slug' => $seller['slug'],
        'headline' => $seller['headline'],
        'city' => $seller['city'],
        'state' => $seller['state'],
        'country' => 'BR',
        'avatar_path' => $seller['avatar'],
        'is_verified' => $seller['verified'],
        'rating_avg' => $seller['rating'],
        'rating_count' => $seller['reviews'],
        'orders_completed' => $seller['orders'],
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $insertWallet->execute([
        'user_id' => $id,
        'currency' => 'BRL',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $sellerIds[$seller['slug']] = $id;
}

$cat = static function (PDO $pdo, string $slug): int {
    return (int) $pdo->query("SELECT id FROM categories WHERE slug = " . $pdo->quote($slug))->fetchColumn();
};

$today = $now;
$older = date('Y-m-d H:i:s', strtotime('-12 days'));

$services = [
    ['user' => 'priscila-costa', 'category' => 'video', 'title' => 'Eu vou gravar o melhor vídeo UGC em até 24h', 'slug' => 'video-ugc-24h', 'short' => 'Vídeo curto, natural e pronto para anúncio.', 'cover' => 'images/svc-edit.jpg', 'price' => 2500, 'days' => 1, 'featured' => 1, 'orders' => 109, 'rating' => 4.75, 'reviews' => 109, 'published' => $older],
    ['user' => 'ian-ramos', 'category' => 'video', 'title' => 'Eu vou gravar um vídeo ou VSL rápido, natural e sem cortes', 'slug' => 'vsl-natural-sem-cortes', 'short' => 'Narração espontânea para funil e ads.', 'cover' => 'images/cat-video.jpg', 'price' => 3500, 'days' => 1, 'featured' => 1, 'orders' => 164, 'rating' => 4.95, 'reviews' => 164, 'published' => $older],
    ['user' => 'jonathan-silva', 'category' => 'video', 'title' => 'Eu vou fazer uma propaganda para seu produto ou serviço', 'slug' => 'propaganda-produto-servico', 'short' => 'Comercial objetivo para redes e site.', 'cover' => 'images/hero-studio.jpg', 'price' => 3000, 'days' => 1, 'featured' => 1, 'orders' => 158, 'rating' => 5.0, 'reviews' => 158, 'published' => $older],
    ['user' => 'jamerson-alves', 'category' => 'video', 'title' => 'Eu vou fazer a melhor propaganda em vídeo para seu negócio', 'slug' => 'propaganda-video-negocio', 'short' => 'Roteiro simples e entrega em 2 dias.', 'cover' => 'images/cat-marketing.jpg', 'price' => 4000, 'days' => 2, 'featured' => 1, 'orders' => 161, 'rating' => 5.0, 'reviews' => 161, 'published' => $older],
    ['user' => 'andressa-vieira', 'category' => 'video', 'title' => 'Eu vou fazer vídeo para apps com demonstração real', 'slug' => 'video-demo-app', 'short' => 'Mostro o app na prática, na tela e nas mãos.', 'cover' => 'images/cat-code.jpg', 'price' => 2500, 'days' => 1, 'featured' => 1, 'orders' => 21, 'rating' => 5.0, 'reviews' => 21, 'published' => $older],
    ['user' => 'luan-rocha', 'category' => 'video', 'title' => 'Eu vou fazer o melhor comercial para sua empresa', 'slug' => 'comercial-empresa', 'short' => 'Tom institucional, direto e memorável.', 'cover' => 'images/svc-edit.jpg', 'price' => 3000, 'days' => 2, 'featured' => 1, 'orders' => 485, 'rating' => 4.9, 'reviews' => 485, 'published' => $older],
    ['user' => 'bruna-souza', 'category' => 'video', 'title' => 'Eu vou gravar vídeos do seu produto ou serviço em 24h', 'slug' => 'video-produto-24h', 'short' => 'Captação rápida com luz natural.', 'cover' => 'images/cat-shop.jpg', 'price' => 2500, 'days' => 1, 'featured' => 1, 'orders' => 195, 'rating' => 5.0, 'reviews' => 195, 'published' => $older],
    ['user' => 'igor-lima', 'category' => 'video', 'title' => 'Eu vou gravar um vídeo natural com entrega rápida', 'slug' => 'video-natural-rapido', 'short' => 'Estilo conversa, sem cara de estúdio.', 'cover' => 'images/cat-video.jpg', 'price' => 2000, 'days' => 1, 'featured' => 1, 'orders' => 2426, 'rating' => 4.95, 'reviews' => 2426, 'published' => $older],
    ['user' => 'eduarda-dias', 'category' => 'video', 'title' => 'Eu vou gravar um vídeo para o seu produto ou serviço', 'slug' => 'video-produto-servico', 'short' => 'Enquadramento limpo e fala clara.', 'cover' => 'images/hero-studio.jpg', 'price' => 5000, 'days' => 3, 'featured' => 1, 'orders' => 56, 'rating' => 5.0, 'reviews' => 56, 'published' => $older],
    ['user' => 'leticia-campos', 'category' => 'video', 'title' => 'Eu vou criar um vídeo já com edição', 'slug' => 'video-ja-editado', 'short' => 'Corte, legenda e música inclusos.', 'cover' => 'images/svc-edit.jpg', 'price' => 5000, 'days' => 2, 'featured' => 1, 'orders' => 80, 'rating' => 5.0, 'reviews' => 80, 'published' => $older],
    ['user' => 'ricardo-santos', 'category' => 'audio-musica', 'title' => 'Eu vou fazer qualquer voz para você usar onde quiser', 'slug' => 'locucao-qualquer-voz', 'short' => 'Locução limpa para ads, vídeos e IVR.', 'cover' => 'images/cat-audio.jpg', 'price' => 2500, 'days' => 1, 'featured' => 0, 'orders' => 2238, 'rating' => 5.0, 'reviews' => 2238, 'published' => $older],
    ['user' => 'jonathan-silva', 'category' => 'video', 'title' => 'Eu vou fazer uma propaganda em até 24 horas', 'slug' => 'propaganda-24h', 'short' => 'Do briefing à entrega no mesmo dia.', 'cover' => 'images/cat-marketing.jpg', 'price' => 2000, 'days' => 1, 'featured' => 0, 'orders' => 2175, 'rating' => 5.0, 'reviews' => 2175, 'published' => $older],
    ['user' => 'bruna-souza', 'category' => 'video', 'title' => 'Eu vou fazer um comercial sobre o seu produto', 'slug' => 'comercial-produto', 'short' => 'Foco em benefício e chamada para ação.', 'cover' => 'images/cat-shop.jpg', 'price' => 2500, 'days' => 2, 'featured' => 0, 'orders' => 2098, 'rating' => 4.95, 'reviews' => 2098, 'published' => $older],
    ['user' => 'monique-ferreira', 'category' => 'video', 'title' => 'Eu vou dar voz e imagem impactantes para seu negócio', 'slug' => 'voz-imagem-negocio', 'short' => 'On camera + locução, entrega em 3h úteis.', 'cover' => 'images/avatar-ana.jpg', 'price' => 4000, 'days' => 1, 'featured' => 0, 'orders' => 1624, 'rating' => 5.0, 'reviews' => 1624, 'published' => $older],
    ['user' => 'lucas-nunes', 'category' => 'marketing', 'title' => 'Eu vou criar 50 backlinks de alta qualidade', 'slug' => 'backlinks-alta-qualidade', 'short' => 'Perfil web 2.0 com ênfase em autoridade.', 'cover' => 'images/cat-data.jpg', 'price' => 4000, 'days' => 30, 'featured' => 0, 'orders' => 1524, 'rating' => 4.8, 'reviews' => 1524, 'published' => $older],
    ['user' => 'ricardo-santos', 'category' => 'audio-musica', 'title' => 'Eu vou transformar seu texto em uma narração incrível', 'slug' => 'narracao-texto', 'short' => 'Tom comercial, educativo ou storytelling.', 'cover' => 'images/cat-audio.jpg', 'price' => 2000, 'days' => 1, 'featured' => 0, 'orders' => 1488, 'rating' => 4.95, 'reviews' => 1488, 'published' => $older],
    ['user' => 'lucas-nunes', 'category' => 'design-grafico', 'title' => 'Eu vou criar seu logotipo em 2 dias úteis', 'slug' => 'logotipo-2-dias', 'short' => 'Marca simples, vetor e aplicações básicas.', 'cover' => 'images/svc-brand.jpg', 'price' => 15000, 'days' => 2, 'featured' => 0, 'orders' => 988, 'rating' => 4.85, 'reviews' => 988, 'published' => $older],
    ['user' => 'ana-freire', 'category' => 'design-grafico', 'title' => 'Eu vou criar a identidade visual da sua marca', 'slug' => 'identidade-visual-marca', 'short' => 'Logo, paleta e usos digitais.', 'cover' => 'images/svc-brand.jpg', 'price' => 20000, 'days' => 5, 'featured' => 0, 'orders' => 19, 'rating' => 5.0, 'reviews' => 16, 'published' => $older],
    ['user' => 'rafael-moura', 'category' => 'video', 'title' => 'Eu vou editar profissionalmente o seu vídeo', 'slug' => 'edicao-video-profissional', 'short' => 'Ritmo, cor e legendas para ads.', 'cover' => 'images/svc-edit.jpg', 'price' => 4000, 'days' => 5, 'featured' => 0, 'orders' => 109, 'rating' => 5.0, 'reviews' => 109, 'published' => $older],
    ['user' => 'daniel-duarte', 'category' => 'programacao', 'title' => 'Eu vou criar um site profissional de alta conversão', 'slug' => 'site-alta-conversao', 'short' => 'Página rápida com CTA e WhatsApp.', 'cover' => 'images/svc-website.jpg', 'price' => 2500, 'days' => 1, 'featured' => 0, 'orders' => 0, 'rating' => 0, 'reviews' => 0, 'published' => $today],
    ['user' => 'camile-vaz', 'category' => 'redacao', 'title' => 'Eu vou fazer digitação de textos e convites digitais', 'slug' => 'digitacao-convites', 'short' => 'Texto caprichado para eventos e loja.', 'cover' => 'images/cat-writing.jpg', 'price' => 20000, 'days' => 4, 'featured' => 0, 'orders' => 0, 'rating' => 0, 'reviews' => 0, 'published' => $today],
    ['user' => 'tiago-dias', 'category' => 'video', 'title' => 'Eu vou gravar um vídeo comercial de até 1 minuto em 24h', 'slug' => 'comercial-1-minuto', 'short' => 'Roteiro curto e entrega no dia seguinte.', 'cover' => 'images/cat-video.jpg', 'price' => 2000, 'days' => 1, 'featured' => 0, 'orders' => 0, 'rating' => 0, 'reviews' => 0, 'published' => $today],
    ['user' => 'tiago-dias', 'category' => 'programacao', 'title' => 'Eu vou criar sua página profissional com botão de WhatsApp', 'slug' => 'pagina-whatsapp', 'short' => 'Landing simples, mobile first.', 'cover' => 'images/svc-wp.jpg', 'price' => 20000, 'days' => 7, 'featured' => 0, 'orders' => 0, 'rating' => 0, 'reviews' => 0, 'published' => $today],
    ['user' => 'igor-lima', 'category' => 'audio-musica', 'title' => 'Eu vou mandar qualquer áudio imitando um personagem', 'slug' => 'audio-personagem', 'short' => 'Imitação leve para meme, loja ou presente.', 'cover' => 'images/cat-audio.jpg', 'price' => 2000, 'days' => 1, 'featured' => 0, 'orders' => 0, 'rating' => 0, 'reviews' => 0, 'published' => $today],
    ['user' => 'luan-rocha', 'category' => 'audio-musica', 'title' => 'Eu vou fazer uma música pra você', 'slug' => 'musica-encomenda', 'short' => 'Letra simples e melodia original.', 'cover' => 'images/cat-audio.jpg', 'price' => 2500, 'days' => 1, 'featured' => 0, 'orders' => 0, 'rating' => 0, 'reviews' => 0, 'published' => $today],
    ['user' => 'ana-freire', 'category' => 'design-grafico', 'title' => 'Eu vou fazer o cardápio da sua lanchonete', 'slug' => 'cardapio-lanchonete', 'short' => 'Arte para imprimir e para o Instagram.', 'cover' => 'images/cat-shop.jpg', 'price' => 2000, 'days' => 1, 'featured' => 0, 'orders' => 0, 'rating' => 0, 'reviews' => 0, 'published' => $today],
    ['user' => 'jamerson-alves', 'category' => 'video', 'title' => 'Eu vou criar propaganda para seu negócio com vídeo de IA', 'slug' => 'propaganda-video-ia', 'short' => 'Roteiro + imagens geradas e voz.', 'cover' => 'images/cat-marketing.jpg', 'price' => 5000, 'days' => 3, 'featured' => 0, 'orders' => 0, 'rating' => 0, 'reviews' => 0, 'published' => $today],
    ['user' => 'rafael-moura', 'category' => 'video', 'title' => 'Eu vou editar vídeos e clipes', 'slug' => 'edicao-clipes', 'short' => 'Corte, beat e legendas para redes.', 'cover' => 'images/svc-edit.jpg', 'price' => 5000, 'days' => 3, 'featured' => 0, 'orders' => 0, 'rating' => 0, 'reviews' => 0, 'published' => $today],
    ['user' => 'leticia-campos', 'category' => 'design-grafico', 'title' => 'Eu vou diagramar seu e-book, criar capa e vídeo promocional', 'slug' => 'ebook-capa-video', 'short' => 'Pacote de lançamento digital.', 'cover' => 'images/cat-writing.jpg', 'price' => 2000, 'days' => 5, 'featured' => 0, 'orders' => 59, 'rating' => 4.75, 'reviews' => 59, 'published' => $older],
    ['user' => 'andressa-vieira', 'category' => 'design-grafico', 'title' => 'Eu vou fazer seus emotes da Twitch ou Discord', 'slug' => 'emotes-twitch', 'short' => 'Pacote expressivo e nítido em PNG.', 'cover' => 'images/cat-design.jpg', 'price' => 2000, 'days' => 5, 'featured' => 0, 'orders' => 14, 'rating' => 5.0, 'reviews' => 14, 'published' => $older],
    ['user' => 'lucas-nunes', 'category' => 'programacao', 'title' => 'Eu vou criar sua landing page de alta conversão', 'slug' => 'landing-alta-conversao', 'short' => 'Uma tela, um recado, um botão.', 'cover' => 'images/svc-website.jpg', 'price' => 30000, 'days' => 3, 'featured' => 0, 'orders' => 20, 'rating' => 5.0, 'reviews' => 20, 'published' => $older],
    ['user' => 'ana-freire', 'category' => 'design-grafico', 'title' => 'Eu vou fazer uma logo para a sua empresa', 'slug' => 'logo-empresa', 'short' => 'Marca limpa, arquivos PNG e SVG.', 'cover' => 'images/svc-brand.jpg', 'price' => 20000, 'days' => 2, 'featured' => 0, 'orders' => 0, 'rating' => 0, 'reviews' => 0, 'published' => $today],
];

$exists = $pdo->prepare('SELECT id FROM services WHERE slug = :s LIMIT 1');
$insertSvc = $pdo->prepare(
    'INSERT INTO services (user_id, category_id, title, slug, short_description, description, status, cover_path, starting_price_cents, min_delivery_days, orders_count, rating_avg, rating_count, is_featured, has_discount, published_at, created_at, updated_at)
     VALUES (:user_id, :category_id, :title, :slug, :short_description, :description, :status, :cover_path, :starting_price_cents, :min_delivery_days, :orders_count, :rating_avg, :rating_count, :is_featured, :has_discount, :published_at, :created_at, :updated_at)'
);
$updateSvc = $pdo->prepare(
    'UPDATE services SET user_id=:user_id, category_id=:category_id, title=:title, short_description=:short_description, description=:description, cover_path=:cover_path, starting_price_cents=:starting_price_cents, min_delivery_days=:min_delivery_days, orders_count=:orders_count, rating_avg=:rating_avg, rating_count=:rating_count, is_featured=:is_featured, published_at=:published_at, status=:status, updated_at=:updated_at WHERE id=:id'
);

foreach ($services as $svc) {
    if (empty($sellerIds[$svc['user']])) {
        continue;
    }
    $payload = [
        'user_id' => $sellerIds[$svc['user']],
        'category_id' => $cat($pdo, $svc['category']),
        'title' => $svc['title'],
        'short_description' => $svc['short'],
        'description' => '<p>' . $svc['short'] . '</p>',
        'cover_path' => $svc['cover'],
        'starting_price_cents' => $svc['price'],
        'min_delivery_days' => $svc['days'],
        'orders_count' => $svc['orders'],
        'rating_avg' => $svc['rating'],
        'rating_count' => $svc['reviews'],
        'is_featured' => $svc['featured'],
        'published_at' => $svc['published'],
        'status' => 'published',
        'updated_at' => $now,
    ];
    $exists->execute(['s' => $svc['slug']]);
    $id = $exists->fetchColumn();
    if ($id) {
        $updateSvc->execute($payload + ['id' => (int) $id]);
        continue;
    }
    $insertSvc->execute($payload + [
        'slug' => $svc['slug'],
        'has_discount' => 0,
        'created_at' => $now,
    ]);
}

$pdo->prepare("UPDATE settings SET setting_value = :v, updated_at = :u WHERE setting_key = :k")
    ->execute(['v' => 'CinquentaConto', 'u' => $now, 'k' => 'platform_name']);
$pdo->prepare("UPDATE settings SET setting_value = :v, updated_at = :u WHERE setting_key = :k")
    ->execute(['v' => 'Preço imbatível. Diversidade inigualável.', 'u' => $now, 'k' => 'tagline']);
$pdo->prepare("UPDATE settings SET setting_value = :v, updated_at = :u WHERE setting_key = :k")
    ->execute(['v' => 'CinquentaConto — transforme seu talento em renda extra', 'u' => $now, 'k' => 'meta_title']);
$pdo->exec("UPDATE banners SET title = 'Transforme seu talento em renda extra hoje!', subtitle = 'Na CinquentaConto, novas oportunidades de trabalho estão à sua espera. Explore projetos da comunidade e transforme suas habilidades em lucro.', cta_label = 'Começar agora', cta_url = '/criar-conta?intent=seller' WHERE placement = 'home_hero'");

foreach (glob(dirname(__DIR__) . '/storage/cache/*.json') ?: [] as $file) {
    @unlink($file);
}

echo "Imagens e vitrine de demonstração aplicadas.\n";

