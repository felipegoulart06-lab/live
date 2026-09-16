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
    [
        'email' => 'ana.freire@nexo.test',
        'name' => 'Ana Freire',
        'slug' => 'ana-freire',
        'headline' => 'Identidade visual e marca',
        'avatar' => 'images/avatar-ana.jpg',
        'city' => 'São Paulo',
        'state' => 'SP',
        'verified' => 1,
        'rating' => 5.0,
        'reviews' => 48,
        'orders' => 62,
    ],
    [
        'email' => 'lucas.nunes@nexo.test',
        'name' => 'Lucas Nunes',
        'slug' => 'lucas-nunes',
        'headline' => 'PHP, WordPress e sistemas',
        'avatar' => 'images/avatar-lucas.jpg',
        'city' => 'Curitiba',
        'state' => 'PR',
        'verified' => 1,
        'rating' => 4.9,
        'reviews' => 31,
        'orders' => 44,
    ],
    [
        'email' => 'rafael.moura@nexo.test',
        'name' => 'Rafael Moura',
        'slug' => 'rafael-moura',
        'headline' => 'Edição e motion',
        'avatar' => 'images/avatar-rafael.jpg',
        'city' => 'Rio de Janeiro',
        'state' => 'RJ',
        'verified' => 0,
        'rating' => 4.8,
        'reviews' => 19,
        'orders' => 22,
    ],
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
$hash = password_hash('NexoDemo!234', PASSWORD_DEFAULT);

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

$services = [
    [
        'user' => 'lucas-nunes',
        'category' => 'programacao',
        'title' => 'Eu vou desenvolver seu site institucional profissional',
        'slug' => 'site-institucional-profissional',
        'short' => 'Layout sob medida, páginas institucionais e formulário de contato.',
        'cover' => 'images/svc-website.jpg',
        'price' => 189000,
        'days' => 10,
        'featured' => 1,
        'orders' => 28,
        'rating' => 4.9,
        'reviews' => 21,
        'fast' => 0,
        'discount' => 0,
    ],
    [
        'user' => 'lucas-nunes',
        'category' => 'programacao',
        'title' => 'Eu vou configurar seu WordPress com tema leve',
        'slug' => 'wordpress-tema-leve',
        'short' => 'Instalação, tema, plugins essenciais e treino rápido de edição.',
        'cover' => 'images/svc-wp.jpg',
        'price' => 89000,
        'days' => 4,
        'featured' => 0,
        'orders' => 41,
        'rating' => 4.8,
        'reviews' => 33,
        'fast' => 1,
        'discount' => 1,
    ],
    [
        'user' => 'ana-freire',
        'category' => 'design-grafico',
        'title' => 'Eu vou criar a identidade visual da sua marca',
        'slug' => 'identidade-visual-marca',
        'short' => 'Logo, paleta, tipografia e aplicações para papelaria digital.',
        'cover' => 'images/svc-brand.jpg',
        'price' => 240000,
        'days' => 12,
        'featured' => 1,
        'orders' => 19,
        'rating' => 5.0,
        'reviews' => 16,
        'fast' => 0,
        'discount' => 0,
    ],
    [
        'user' => 'rafael-moura',
        'category' => 'video',
        'title' => 'Eu vou editar seu vídeo comercial com ritmo de marca',
        'slug' => 'edicao-video-comercial',
        'short' => 'Corte, cor, legendas e entrega em formatos para ads e site.',
        'cover' => 'images/svc-edit.jpg',
        'price' => 120000,
        'days' => 5,
        'featured' => 0,
        'orders' => 15,
        'rating' => 4.8,
        'reviews' => 11,
        'fast' => 1,
        'discount' => 1,
    ],
];

$exists = $pdo->prepare('SELECT id FROM services WHERE user_id = :u AND slug = :s LIMIT 1');
$insertSvc = $pdo->prepare(
    'INSERT INTO services (user_id, category_id, title, slug, short_description, description, status, cover_path, starting_price_cents, min_delivery_days, orders_count, rating_avg, rating_count, is_featured, has_discount, published_at, created_at, updated_at)
     VALUES (:user_id, :category_id, :title, :slug, :short_description, :description, :status, :cover_path, :starting_price_cents, :min_delivery_days, :orders_count, :rating_avg, :rating_count, :is_featured, :has_discount, :published_at, :created_at, :updated_at)'
);

foreach ($services as $svc) {
    $userId = $sellerIds[$svc['user']];
    $exists->execute(['u' => $userId, 's' => $svc['slug']]);
    if ($exists->fetchColumn()) {
        continue;
    }
    $insertSvc->execute([
        'user_id' => $userId,
        'category_id' => $cat($pdo, $svc['category']),
        'title' => $svc['title'],
        'slug' => $svc['slug'],
        'short_description' => $svc['short'],
        'description' => '<p>' . $svc['short'] . '</p>',
        'status' => 'published',
        'cover_path' => $svc['cover'],
        'starting_price_cents' => $svc['price'],
        'min_delivery_days' => $svc['days'],
        'orders_count' => $svc['orders'],
        'rating_avg' => $svc['rating'],
        'rating_count' => $svc['reviews'],
        'is_featured' => $svc['featured'],
        'has_discount' => $svc['discount'],
        'published_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

foreach (glob(dirname(__DIR__) . '/storage/cache/*.json') ?: [] as $file) {
    @unlink($file);
}

echo "Imagens e vitrine de demonstração aplicadas.\n";
