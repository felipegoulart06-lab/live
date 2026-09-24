<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$pdo = \App\Core\Database::pdo();

$now = date('Y-m-d H:i:s');

$images = [
    'institucional' => 'images/hero-studio.jpg',
    'produto' => 'images/cat-shop.jpg',
    'treinamento' => 'images/cat-business.jpg',
    'comercial' => 'images/cat-marketing.jpg',
    'ugc' => 'images/cat-video.jpg',
    'eventos' => 'images/cat-photo.jpg',
    'locucao' => 'images/cat-audio.jpg',
    'edicao' => 'images/svc-edit.jpg',
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
    ['email' => 'ana.freire@cinquentaconto.test', 'name' => 'Ana Freire', 'slug' => 'ana-freire', 'headline' => 'Vídeo institucional para empresas', 'avatar' => 'images/avatar-ana.jpg', 'city' => 'São Paulo', 'state' => 'SP', 'verified' => 1, 'rating' => 5.0, 'reviews' => 48, 'orders' => 62],
    ['email' => 'lucas.nunes@cinquentaconto.test', 'name' => 'Lucas Nunes', 'slug' => 'lucas-nunes', 'headline' => 'Treinamento interno em vídeo', 'avatar' => 'images/avatar-lucas.jpg', 'city' => 'Curitiba', 'state' => 'PR', 'verified' => 1, 'rating' => 4.9, 'reviews' => 31, 'orders' => 44],
    ['email' => 'rafael.moura@cinquentaconto.test', 'name' => 'Rafael Moura', 'slug' => 'rafael-moura', 'headline' => 'Edição de horas de vídeo', 'avatar' => 'images/avatar-rafael.jpg', 'city' => 'Rio de Janeiro', 'state' => 'RJ', 'verified' => 0, 'rating' => 4.8, 'reviews' => 19, 'orders' => 22],
    ['email' => 'priscila.costa@cinquentaconto.test', 'name' => 'Priscila Costa', 'slug' => 'priscila-costa', 'headline' => 'UGC para marca corporativa', 'avatar' => 'images/avatar-marina.jpg', 'city' => 'Belo Horizonte', 'state' => 'MG', 'verified' => 1, 'rating' => 4.75, 'reviews' => 109, 'orders' => 109],
    ['email' => 'ian.ramos@cinquentaconto.test', 'name' => 'Ian Ramos', 'slug' => 'ian-ramos', 'headline' => 'Comercial e VSL sob briefing', 'avatar' => 'images/avatar-lucas.jpg', 'city' => 'Recife', 'state' => 'PE', 'verified' => 1, 'rating' => 4.95, 'reviews' => 164, 'orders' => 164],
    ['email' => 'jonathan.silva@cinquentaconto.test', 'name' => 'Jonathan Silva', 'slug' => 'jonathan-silva', 'headline' => 'Propaganda com tema da empresa', 'avatar' => 'images/avatar-rafael.jpg', 'city' => 'Fortaleza', 'state' => 'CE', 'verified' => 1, 'rating' => 5.0, 'reviews' => 158, 'orders' => 158],
    ['email' => 'jamerson.alves@cinquentaconto.test', 'name' => 'Jamerson Alves', 'slug' => 'jamerson-alves', 'headline' => 'Horas de comercial empresarial', 'avatar' => 'images/avatar-lucas.jpg', 'city' => 'Salvador', 'state' => 'BA', 'verified' => 1, 'rating' => 5.0, 'reviews' => 161, 'orders' => 161],
    ['email' => 'andressa.vieira@cinquentaconto.test', 'name' => 'Andressa Vieira', 'slug' => 'andressa-vieira', 'headline' => 'Demo de produto em vídeo', 'avatar' => 'images/avatar-helena.jpg', 'city' => 'Campinas', 'state' => 'SP', 'verified' => 1, 'rating' => 5.0, 'reviews' => 21, 'orders' => 21],
    ['email' => 'luan.rocha@cinquentaconto.test', 'name' => 'Luan Rocha', 'slug' => 'luan-rocha', 'headline' => 'Institucional e cultura', 'avatar' => 'images/avatar-rafael.jpg', 'city' => 'Porto Alegre', 'state' => 'RS', 'verified' => 1, 'rating' => 4.9, 'reviews' => 485, 'orders' => 485],
    ['email' => 'bruna.souza@cinquentaconto.test', 'name' => 'Bruna Souza', 'slug' => 'bruna-souza', 'headline' => 'Vídeo de produto e serviço', 'avatar' => 'images/avatar-ana.jpg', 'city' => 'Goiânia', 'state' => 'GO', 'verified' => 1, 'rating' => 5.0, 'reviews' => 195, 'orders' => 195],
    ['email' => 'igor.lima@cinquentaconto.test', 'name' => 'Igor Lima', 'slug' => 'igor-lima', 'headline' => 'Horas naturais em câmera', 'avatar' => 'images/avatar-lucas.jpg', 'city' => 'Manaus', 'state' => 'AM', 'verified' => 1, 'rating' => 4.95, 'reviews' => 2426, 'orders' => 2426],
    ['email' => 'eduarda.dias@cinquentaconto.test', 'name' => 'Eduarda Dias', 'slug' => 'eduarda-dias', 'headline' => 'Serviço da empresa em vídeo', 'avatar' => 'images/avatar-helena.jpg', 'city' => 'Vitória', 'state' => 'ES', 'verified' => 1, 'rating' => 5.0, 'reviews' => 56, 'orders' => 56],
    ['email' => 'leticia.campos@cinquentaconto.test', 'name' => 'Leticia Campos', 'slug' => 'leticia-campos', 'headline' => 'Captação + edição por hora', 'avatar' => 'images/avatar-marina.jpg', 'city' => 'Natal', 'state' => 'RN', 'verified' => 1, 'rating' => 5.0, 'reviews' => 80, 'orders' => 80],
    ['email' => 'ricardo.santos@cinquentaconto.test', 'name' => 'Ricardo Santos', 'slug' => 'ricardo-santos', 'headline' => 'Locução para vídeo corporativo', 'avatar' => 'images/avatar-rafael.jpg', 'city' => 'Brasília', 'state' => 'DF', 'verified' => 1, 'rating' => 5.0, 'reviews' => 2238, 'orders' => 2238],
    ['email' => 'monique.ferreira@cinquentaconto.test', 'name' => 'Monique Ferreira', 'slug' => 'monique-ferreira', 'headline' => 'Voz e imagem para empresas', 'avatar' => 'images/avatar-ana.jpg', 'city' => 'Florianópolis', 'state' => 'SC', 'verified' => 1, 'rating' => 5.0, 'reviews' => 1624, 'orders' => 1624],
    ['email' => 'daniel.duarte@cinquentaconto.test', 'name' => 'Daniel Duarte', 'slug' => 'daniel-duarte', 'headline' => 'Eventos e cobertura interna', 'avatar' => 'images/avatar-lucas.jpg', 'city' => 'São Luís', 'state' => 'MA', 'verified' => 0, 'rating' => 0, 'reviews' => 0, 'orders' => 0],
    ['email' => 'camile.vaz@cinquentaconto.test', 'name' => 'Camile Vaz', 'slug' => 'camile-vaz', 'headline' => 'Onboarding em vídeo', 'avatar' => 'images/avatar-helena.jpg', 'city' => 'João Pessoa', 'state' => 'PB', 'verified' => 0, 'rating' => 0, 'reviews' => 0, 'orders' => 0],
    ['email' => 'tiago.dias@cinquentaconto.test', 'name' => 'Tiago Dias', 'slug' => 'tiago-dias', 'headline' => 'Horas para TV corporativa', 'avatar' => 'images/avatar-rafael.jpg', 'city' => 'Maceió', 'state' => 'AL', 'verified' => 0, 'rating' => 0, 'reviews' => 0, 'orders' => 0],
];

$faces = [
    'ana-freire' => 'images/face-01.jpg',
    'lucas-nunes' => 'images/face-02.jpg',
    'rafael-moura' => 'images/face-03.jpg',
    'priscila-costa' => 'images/face-04.jpg',
    'ian-ramos' => 'images/face-05.jpg',
    'jonathan-silva' => 'images/face-06.jpg',
    'jamerson-alves' => 'images/face-11.jpg',
    'andressa-vieira' => 'images/face-07.jpg',
    'luan-rocha' => 'images/face-09.jpg',
    'bruna-souza' => 'images/face-08.jpg',
    'igor-lima' => 'images/face-21.jpg',
    'eduarda-dias' => 'images/face-10.jpg',
    'leticia-campos' => 'images/face-12.jpg',
    'ricardo-santos' => 'images/face-15.jpg',
    'monique-ferreira' => 'images/face-14.jpg',
    'daniel-duarte' => 'images/face-20.jpg',
    'camile-vaz' => 'images/face-16.jpg',
    'tiago-dias' => 'images/face-19.jpg',
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
        $face = $faces[$seller['slug']] ?? $seller['avatar'];
        $pdo->prepare('UPDATE profiles SET avatar_path = :a, headline = :h, is_verified = :v, rating_avg = :r, rating_count = :c, orders_completed = :o WHERE user_id = :id')
            ->execute([
                'a' => $face,
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
        'avatar_path' => $faces[$seller['slug']] ?? $seller['avatar'],
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
    ['user' => 'priscila-costa', 'category' => 'ugc', 'title' => 'Eu vou gravar horas de UGC com o tema da sua empresa', 'slug' => 'horas-ugc-empresa', 'short' => '2 a 8 horas. Você define o recado; eu apareço em câmera.', 'cover' => 'images/svc-edit.jpg', 'price' => 25000, 'days' => 4, 'featured' => 1, 'orders' => 109, 'rating' => 4.75, 'reviews' => 109, 'published' => $older],
    ['user' => 'ian-ramos', 'category' => 'comercial', 'title' => 'Eu vou gravar 2 a 8 horas de comercial ou VSL', 'slug' => 'horas-comercial-vsl', 'short' => 'O tema do anúncio é da empresa. Entrega em blocos de horas.', 'cover' => 'images/cat-video.jpg', 'price' => 35000, 'days' => 5, 'featured' => 1, 'orders' => 164, 'rating' => 4.95, 'reviews' => 164, 'published' => $older],
    ['user' => 'jonathan-silva', 'category' => 'comercial', 'title' => 'Eu vou fazer propaganda em vídeo com o briefing de vocês', 'slug' => 'propaganda-briefing-empresa', 'short' => 'Horas de captação. Produto, serviço ou campanha — vocês escolhem.', 'cover' => 'images/hero-studio.jpg', 'price' => 30000, 'days' => 4, 'featured' => 1, 'orders' => 158, 'rating' => 5.0, 'reviews' => 158, 'published' => $older],
    ['user' => 'jamerson-alves', 'category' => 'institucional', 'title' => 'Eu vou gravar o institucional da sua empresa em horas', 'slug' => 'institucional-horas', 'short' => 'Cultura, apresentação e posicionamento com o tema que vocês enviarem.', 'cover' => 'images/cat-marketing.jpg', 'price' => 40000, 'days' => 7, 'featured' => 1, 'orders' => 161, 'rating' => 5.0, 'reviews' => 161, 'published' => $older],
    ['user' => 'andressa-vieira', 'category' => 'produto', 'title' => 'Eu vou demonstrar o produto da sua empresa em vídeo', 'slug' => 'demo-produto-horas', 'short' => 'Blocos de horas para mostrar o que vocês vendem, no tom combinado.', 'cover' => 'images/cat-code.jpg', 'price' => 25000, 'days' => 4, 'featured' => 1, 'orders' => 21, 'rating' => 5.0, 'reviews' => 21, 'published' => $older],
    ['user' => 'luan-rocha', 'category' => 'institucional', 'title' => 'Eu vou gravar horas de comercial para a sua empresa', 'slug' => 'comercial-empresa-horas', 'short' => 'Tom institucional. O assunto sai do briefing de quem paga.', 'cover' => 'images/svc-edit.jpg', 'price' => 30000, 'days' => 6, 'featured' => 1, 'orders' => 485, 'rating' => 4.9, 'reviews' => 485, 'published' => $older],
    ['user' => 'bruna-souza', 'category' => 'produto', 'title' => 'Eu vou gravar horas de vídeo do seu produto ou serviço', 'slug' => 'horas-produto-servico', 'short' => 'Captação por hora. Tema, locação e fala definidos pela empresa.', 'cover' => 'images/cat-shop.jpg', 'price' => 25000, 'days' => 4, 'featured' => 1, 'orders' => 195, 'rating' => 5.0, 'reviews' => 195, 'published' => $older],
    ['user' => 'igor-lima', 'category' => 'ugc', 'title' => 'Eu vou entregar horas de vídeo natural para a sua marca', 'slug' => 'horas-video-natural', 'short' => 'Estilo conversa. Sem telefone no anúncio; o tema chega no pedido.', 'cover' => 'images/cat-video.jpg', 'price' => 20000, 'days' => 3, 'featured' => 1, 'orders' => 426, 'rating' => 4.95, 'reviews' => 426, 'published' => $older],
    ['user' => 'eduarda-dias', 'category' => 'produto', 'title' => 'Eu vou gravar o serviço da sua empresa em blocos de horas', 'slug' => 'horas-servico-empresa', 'short' => 'Mostro o atendimento ou a oferta que vocês descreverem.', 'cover' => 'images/hero-studio.jpg', 'price' => 28000, 'days' => 5, 'featured' => 1, 'orders' => 56, 'rating' => 5.0, 'reviews' => 56, 'published' => $older],
    ['user' => 'leticia-campos', 'category' => 'edicao', 'title' => 'Eu vou editar as horas de vídeo que a empresa enviar', 'slug' => 'edicao-horas-empresa', 'short' => 'Corte, legenda e versões. O tema e o material vêm de vocês.', 'cover' => 'images/svc-edit.jpg', 'price' => 22000, 'days' => 5, 'featured' => 1, 'orders' => 80, 'rating' => 5.0, 'reviews' => 80, 'published' => $older],
    ['user' => 'ricardo-santos', 'category' => 'locucao', 'title' => 'Eu vou narrar as horas de vídeo da sua empresa', 'slug' => 'locucao-horas-empresa', 'short' => 'Locução sob o texto que vocês enviarem. Sem contato público.', 'cover' => 'images/cat-audio.jpg', 'price' => 18000, 'days' => 3, 'featured' => 0, 'orders' => 238, 'rating' => 5.0, 'reviews' => 238, 'published' => $older],
    ['user' => 'jonathan-silva', 'category' => 'comercial', 'title' => 'Eu vou gravar horas de anúncio com o tema do lançamento', 'slug' => 'horas-anuncio-lancamento', 'short' => 'Vocês definem oferta e CTA. Eu gravo o bloco contratado.', 'cover' => 'images/cat-marketing.jpg', 'price' => 32000, 'days' => 4, 'featured' => 0, 'orders' => 175, 'rating' => 5.0, 'reviews' => 175, 'published' => $older],
    ['user' => 'bruna-souza', 'category' => 'produto', 'title' => 'Eu vou fazer comercial de produto em pacotes de horas', 'slug' => 'comercial-produto-horas', 'short' => 'Foco no benefício que a empresa escrever no briefing.', 'cover' => 'images/cat-shop.jpg', 'price' => 26000, 'days' => 5, 'featured' => 0, 'orders' => 98, 'rating' => 4.95, 'reviews' => 98, 'published' => $older],
    ['user' => 'monique-ferreira', 'category' => 'institucional', 'title' => 'Eu vou dar voz e imagem para o vídeo da sua empresa', 'slug' => 'voz-imagem-empresa', 'short' => 'On camera + locução. Tema interno ou de marca, definido por vocês.', 'cover' => 'images/avatar-ana.jpg', 'price' => 40000, 'days' => 6, 'featured' => 0, 'orders' => 164, 'rating' => 5.0, 'reviews' => 164, 'published' => $older],
    ['user' => 'lucas-nunes', 'category' => 'treinamento', 'title' => 'Eu vou gravar horas de treinamento interno', 'slug' => 'horas-treinamento-interno', 'short' => 'Processo, produto ou atendimento. O roteiro é da empresa.', 'cover' => 'images/cat-business.jpg', 'price' => 38000, 'days' => 8, 'featured' => 0, 'orders' => 44, 'rating' => 4.8, 'reviews' => 44, 'published' => $older],
    ['user' => 'ana-freire', 'category' => 'institucional', 'title' => 'Eu vou gravar a apresentação institucional da sua marca', 'slug' => 'apresentacao-institucional-horas', 'short' => 'Blocos de 2 a 8 horas. Cultura e posicionamento no tom combinado.', 'cover' => 'images/svc-brand.jpg', 'price' => 42000, 'days' => 8, 'featured' => 0, 'orders' => 19, 'rating' => 5.0, 'reviews' => 16, 'published' => $older],
    ['user' => 'rafael-moura', 'category' => 'edicao', 'title' => 'Eu vou editar as horas que o time da empresa gravou', 'slug' => 'edicao-material-interno', 'short' => 'Ritmo, cor e legendas para o vídeo corporativo de vocês.', 'cover' => 'images/svc-edit.jpg', 'price' => 24000, 'days' => 5, 'featured' => 0, 'orders' => 109, 'rating' => 5.0, 'reviews' => 109, 'published' => $older],
    ['user' => 'daniel-duarte', 'category' => 'eventos', 'title' => 'Eu vou cobrir o evento da sua empresa em horas de vídeo', 'slug' => 'cobertura-evento-horas', 'short' => 'Feira, convenção ou loja. Vocês definem o recorte.', 'cover' => 'images/cat-photo.jpg', 'price' => 45000, 'days' => 7, 'featured' => 0, 'orders' => 0, 'rating' => 0, 'reviews' => 0, 'published' => $today],
    ['user' => 'camile-vaz', 'category' => 'treinamento', 'title' => 'Eu vou gravar onboarding em vídeo para o seu time', 'slug' => 'onboarding-video-horas', 'short' => 'Horas para explicar processo interno. Tema e slides vêm de vocês.', 'cover' => 'images/cat-writing.jpg', 'price' => 30000, 'days' => 6, 'featured' => 0, 'orders' => 0, 'rating' => 0, 'reviews' => 0, 'published' => $today],
    ['user' => 'tiago-dias', 'category' => 'comercial', 'title' => 'Eu vou gravar horas de vídeo para TV corporativa', 'slug' => 'tv-corporativa-horas', 'short' => 'Peças internas. O tema do dia é o que a empresa enviar.', 'cover' => 'images/cat-video.jpg', 'price' => 28000, 'days' => 4, 'featured' => 0, 'orders' => 0, 'rating' => 0, 'reviews' => 0, 'published' => $today],
    ['user' => 'igor-lima', 'category' => 'ugc', 'title' => 'Eu vou gravar depoimentos com o roteiro da sua empresa', 'slug' => 'depoimento-roteiro-empresa', 'short' => 'Cliente, time ou marca. Sem WhatsApp no perfil público.', 'cover' => 'images/cat-audio.jpg', 'price' => 22000, 'days' => 4, 'featured' => 0, 'orders' => 0, 'rating' => 0, 'reviews' => 0, 'published' => $today],
    ['user' => 'luan-rocha', 'category' => 'eventos', 'title' => 'Eu vou registrar horas na fábrica ou na loja', 'slug' => 'horas-fabrica-loja', 'short' => 'Bastidores da operação. Vocês apontam o que pode aparecer.', 'cover' => 'images/cat-business.jpg', 'price' => 36000, 'days' => 7, 'featured' => 0, 'orders' => 0, 'rating' => 0, 'reviews' => 0, 'published' => $today],
    ['user' => 'ana-freire', 'category' => 'ugc', 'title' => 'Eu vou gravar reels com o tema que a empresa mandar', 'slug' => 'reels-tema-empresa', 'short' => 'Pacotes de horas para redes. Contato só pela plataforma.', 'cover' => 'images/cat-video.jpg', 'price' => 20000, 'days' => 3, 'featured' => 0, 'orders' => 0, 'rating' => 0, 'reviews' => 0, 'published' => $today],
    ['user' => 'jamerson-alves', 'category' => 'comercial', 'title' => 'Eu vou produzir horas de anúncio a partir do briefing', 'slug' => 'anuncio-briefing-horas', 'short' => 'Vocês definem oferta e público. Eu entrego o bloco de horas.', 'cover' => 'images/cat-marketing.jpg', 'price' => 34000, 'days' => 5, 'featured' => 0, 'orders' => 0, 'rating' => 0, 'reviews' => 0, 'published' => $today],
    ['user' => 'leticia-campos', 'category' => 'edicao', 'title' => 'Eu vou cortar e legendas as horas do evento interno', 'slug' => 'edicao-evento-interno', 'short' => 'Material da empresa, edição por bloco de horas.', 'cover' => 'images/svc-edit.jpg', 'price' => 21000, 'days' => 5, 'featured' => 0, 'orders' => 12, 'rating' => 4.75, 'reviews' => 12, 'published' => $older],
    ['user' => 'andressa-vieira', 'category' => 'produto', 'title' => 'Eu vou gravar demonstração real do que vocês vendem', 'slug' => 'demo-real-empresa', 'short' => 'App, produto físico ou serviço. O roteiro é de quem paga.', 'cover' => 'images/cat-shop.jpg', 'price' => 27000, 'days' => 5, 'featured' => 0, 'orders' => 14, 'rating' => 5.0, 'reviews' => 14, 'published' => $older],
    ['user' => 'lucas-nunes', 'category' => 'treinamento', 'title' => 'Eu vou gravar o processo da sua operação em vídeo', 'slug' => 'processo-operacao-horas', 'short' => 'Passo a passo interno. Horas contratadas, tema fechado por vocês.', 'cover' => 'images/cat-business.jpg', 'price' => 39000, 'days' => 9, 'featured' => 0, 'orders' => 20, 'rating' => 5.0, 'reviews' => 20, 'published' => $older],
    ['user' => 'monique-ferreira', 'category' => 'locucao', 'title' => 'Eu vou locutar o vídeo institucional que vocês enviarem', 'slug' => 'locucao-institucional-horas', 'short' => 'Voz para o material da empresa. Sem telefone no anúncio.', 'cover' => 'images/cat-audio.jpg', 'price' => 19000, 'days' => 3, 'featured' => 0, 'orders' => 40, 'rating' => 5.0, 'reviews' => 40, 'published' => $today],
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
    $categoryId = $cat($pdo, $svc['category']);
    if ($categoryId <= 0 || empty($sellerIds[$svc['user']])) {
        continue;
    }
    $payload = [
        'user_id' => $sellerIds[$svc['user']],
        'category_id' => $categoryId,
        'title' => $svc['title'],
        'short_description' => $svc['short'],
        'description' => '<p>' . $svc['short'] . '</p>',
        'cover_path' => $faces[$svc['user']] ?? $svc['cover'],
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
    ->execute(['v' => 'Horas de vídeo para a sua empresa. Tema definido por quem paga.', 'u' => $now, 'k' => 'tagline']);
$pdo->prepare("UPDATE settings SET setting_value = :v, updated_at = :u WHERE setting_key = :k")
    ->execute(['v' => 'CinquentaConto — horas de vídeo para a sua empresa', 'u' => $now, 'k' => 'meta_title']);
$pdo->prepare("UPDATE settings SET setting_value = :v, updated_at = :u WHERE setting_key = :k")
    ->execute(['v' => 'Empresas encontram criadores para gravar 2, 4, 6 ou 8 horas de vídeo. O tema é de quem paga. Telefone e WhatsApp não ficam expostos.', 'u' => $now, 'k' => 'meta_description']);
$pdo->exec("UPDATE banners SET title = 'Encontre quem grava o vídeo da sua empresa', subtitle = 'Contrate 2, 4, 6 ou 8 horas de vídeo. O tema é definido por quem paga. Telefone e WhatsApp do criador não aparecem no anúncio.', cta_label = 'Ver criadores', cta_url = '/buscar' WHERE placement = 'home_hero'");

foreach (glob(dirname(__DIR__) . '/storage/cache/*.json') ?: [] as $file) {
    @unlink($file);
}

echo "Imagens e vitrine de demonstração aplicadas.\n";

