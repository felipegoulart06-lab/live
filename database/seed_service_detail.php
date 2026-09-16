<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$pdo = \App\Core\Database::pdo();

$now = date('Y-m-d H:i:s');

$skills = ['PHP', 'WordPress', 'UX/UI', 'Identidade visual', 'Edição de vídeo', 'SEO', 'MySQL', 'Figma'];
$insSkill = $pdo->prepare('INSERT OR IGNORE INTO skills (name, slug, created_at) VALUES (:n, :s, :c)');
foreach ($skills as $name) {
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name));
    $insSkill->execute(['n' => $name, 's' => trim($slug, '-'), 'c' => $now]);
}

$skillIds = [];
foreach ($pdo->query('SELECT id, name FROM skills') as $row) {
    $skillIds[$row['name']] = (int) $row['id'];
}
$langIds = [];
foreach ($pdo->query('SELECT id, code FROM languages') as $row) {
    $langIds[$row['code']] = (int) $row['id'];
}
$badgeIds = [];
foreach ($pdo->query('SELECT id, slug FROM badges') as $row) {
    $badgeIds[$row['slug']] = (int) $row['id'];
}

$profiles = [
    'ana-freire' => [
        'bio' => 'Designer de marca com foco em sistemas visuais claros para empresas que precisam parecer tão sólidas quanto o serviço que entregam.',
        'experience' => 8,
        'response' => 98,
        'minutes' => 35,
        'skills' => ['Identidade visual', 'UX/UI', 'Figma'],
        'langs' => [['pt', 'native'], ['en', 'fluent']],
        'badges' => ['verified', 'top-freelancer'],
        'seen' => date('Y-m-d H:i:s', time() - 120),
    ],
    'lucas-nunes' => [
        'bio' => 'Desenvolvedor PHP e WordPress. Trabalho com briefing fechado, prazo visível e entrega que o time do cliente consegue manter depois.',
        'experience' => 11,
        'response' => 96,
        'minutes' => 50,
        'skills' => ['PHP', 'WordPress', 'MySQL', 'SEO'],
        'langs' => [['pt', 'native'], ['en', 'intermediate']],
        'badges' => ['verified', 'fast-delivery', 'sales-100'],
        'seen' => date('Y-m-d H:i:s', time() - 80),
    ],
    'rafael-moura' => [
        'bio' => 'Editor de vídeo para marcas e conteúdo. Ritmo, cor e legendas pensados para anúncio e para o site, sem excesso de efeito.',
        'experience' => 6,
        'response' => 91,
        'minutes' => 70,
        'skills' => ['Edição de vídeo'],
        'langs' => [['pt', 'native'], ['es', 'basic']],
        'badges' => ['fast-delivery', 'new-seller'],
        'seen' => date('Y-m-d H:i:s', time() - 900),
    ],
];

foreach ($profiles as $slug => $info) {
    $userId = (int) $pdo->query('SELECT user_id FROM profiles WHERE slug = ' . $pdo->quote($slug))->fetchColumn();
    if (!$userId) {
        continue;
    }
    $pdo->prepare(
        'UPDATE profiles SET bio = :bio, experience_years = :exp, response_rate = :rr, avg_response_minutes = :mins, updated_at = :u WHERE user_id = :id'
    )->execute([
        'bio' => $info['bio'],
        'exp' => $info['experience'],
        'rr' => $info['response'],
        'mins' => $info['minutes'],
        'u' => $now,
        'id' => $userId,
    ]);
    $pdo->prepare('UPDATE users SET last_seen_at = :s WHERE id = :id')->execute(['s' => $info['seen'], 'id' => $userId]);

    $pdo->prepare('DELETE FROM user_skills WHERE user_id = :id')->execute(['id' => $userId]);
    $linkSkill = $pdo->prepare('INSERT OR IGNORE INTO user_skills (user_id, skill_id) VALUES (:u, :s)');
    foreach ($info['skills'] as $skillName) {
        if (!empty($skillIds[$skillName])) {
            $linkSkill->execute(['u' => $userId, 's' => $skillIds[$skillName]]);
        }
    }

    $pdo->prepare('DELETE FROM user_languages WHERE user_id = :id')->execute(['id' => $userId]);
    $linkLang = $pdo->prepare('INSERT OR IGNORE INTO user_languages (user_id, language_id, level) VALUES (:u, :l, :lv)');
    foreach ($info['langs'] as [$code, $level]) {
        if (!empty($langIds[$code])) {
            $linkLang->execute(['u' => $userId, 'l' => $langIds[$code], 'lv' => $level]);
        }
    }

    $linkBadge = $pdo->prepare('INSERT OR IGNORE INTO user_badges (user_id, badge_id, awarded_at) VALUES (:u, :b, :a)');
    foreach ($info['badges'] as $badgeSlug) {
        if (!empty($badgeIds[$badgeSlug])) {
            $linkBadge->execute(['u' => $userId, 'b' => $badgeIds[$badgeSlug], 'a' => $now]);
        }
    }
}

$details = [
    'site-institucional-profissional' => [
        'description' => '<h2>O que você recebe</h2><p>Um site institucional com estrutura clara, pronto para apresentar a empresa, serviços e contato. Trabalho a partir de um briefing objetivo e deixo o código organizado para o seu time manter.</p><h2>Como funciona</h2><ul><li>Alinhamento de páginas, referências e prazo</li><li>Layout e implementação</li><li>Formulário de contato e ajustes finais</li></ul><p>Hospedagem e domínio ficam a cargo do cliente, salvo se contratar o adicional de instalação.</p>',
        'packages' => [
            ['basic', 'Presença essencial', 'Até 5 páginas, layout sob medida e formulário.', 189000, 10, 2, 1, ['Até 5 páginas', 'Layout responsivo', 'Formulário de contato', '2 revisões']],
            ['standard', 'Institucional completo', 'Até 8 páginas, blog simples e SEO básico.', 289000, 14, 3, 1, ['Até 8 páginas', 'Blog simples', 'SEO on-page', '3 revisões']],
            ['premium', 'Site + painel', 'Até 12 páginas, área administrativa e treino.', 420000, 21, 4, 1, ['Até 12 páginas', 'Painel de conteúdo', 'Treinamento de 1h', '4 revisões']],
        ],
        'extras' => [
            ['Entrega em 7 dias', 'Prioridade na fila, sujeito à agenda.', 90000, 0],
            ['Página adicional', 'Uma página extra no mesmo estilo visual.', 35000, 2],
            ['Instalação em hospedagem', 'Publicação no servidor que você indicar.', 25000, 1],
        ],
        'faqs' => [
            ['Vocês fazem a arte do zero?', 'Sim. Se já houver identidade, uso como base. Se não houver, o pacote intermediário já cobre um visual consistente.'],
            ['O site fica em WordPress?', 'Este serviço é sob medida em PHP. Para WordPress, use o outro serviço do mesmo profissional.'],
        ],
        'gallery' => ['images/svc-website.jpg', 'images/cat-code.jpg', 'images/svc-wp.jpg'],
        'sub' => 'websites',
    ],
    'wordpress-tema-leve' => [
        'description' => '<h2>WordPress sem peso</h2><p>Instalo, configuro tema leve, plugins essenciais e deixo um guia curto de edição. O objetivo é um site que o cliente atualiza sem quebrar o layout.</p><ul><li>Tema e plugins alinhados ao briefing</li><li>Páginas iniciais</li><li>Backup e treino rápido</li></ul>',
        'packages' => [
            ['basic', 'Instalação', 'WordPress, tema e 4 páginas.', 89000, 4, 1, 1, ['Instalação', 'Até 4 páginas', '1 revisão']],
            ['standard', 'Loja ou institucional', 'Até 8 páginas e formulários.', 149000, 7, 2, 1, ['Até 8 páginas', 'Formulários', '2 revisões']],
            ['premium', 'Com SEO e velocidade', 'Otimização básica de performance e SEO.', 219000, 10, 3, 1, ['Performance', 'SEO básico', '3 revisões']],
        ],
        'extras' => [
            ['Entrega em 48h', 'Fila prioritária.', 40000, 0],
            ['Hospedagem assistida', 'Ajuda na conta de hospedagem indicada.', 18000, 1],
        ],
        'faqs' => [
            ['Preciso fornecer o tema?', 'Pode ser tema indicado por você ou um tema leve que eu proponho no briefing.'],
        ],
        'gallery' => ['images/svc-wp.jpg', 'images/cat-code.jpg'],
        'sub' => 'wordpress',
    ],
    'identidade-visual-marca' => [
        'description' => '<h2>Sistema visual, não só um logo</h2><p>Entrego marca, paleta, tipografia e aplicações para a empresa usar com consistência em site, proposta e redes.</p><ul><li>Pesquisa rápida de posicionamento</li><li>Direções de marca</li><li>Arquivos finais organizados</li></ul>',
        'packages' => [
            ['basic', 'Marca essencial', 'Logo e paleta.', 240000, 12, 2, 1, ['Logo principal', 'Paleta', '2 revisões']],
            ['standard', 'Identidade completa', 'Logo, tipografia e papelaria digital.', 360000, 16, 3, 1, ['Tipografia', 'Cartão e capa', '3 revisões']],
            ['premium', 'Brand kit', 'Manual resumido e variações para redes.', 520000, 22, 4, 1, ['Manual resumido', 'Kit redes', '4 revisões']],
        ],
        'extras' => [
            ['Logo adicional (submarca)', 'Variação para um produto ou unidade.', 80000, 4],
            ['Revisão extra', 'Uma rodada além das inclusas.', 20000, 2],
        ],
        'faqs' => [
            ['Quantas propostas iniciais?', 'No básico, uma direção. No intermediário, duas. No premium, três caminhos resumidos.'],
        ],
        'gallery' => ['images/svc-brand.jpg', 'images/cat-design.jpg', 'images/cat-writing.jpg'],
        'sub' => 'identidade-visual',
    ],
    'edicao-video-comercial' => [
        'description' => '<h2>Vídeo com ritmo de marca</h2><p>Corte, cor, legendas e versões para anúncio e site. Trabalho com o material que você gravou ou com takes de banco, se combinado no briefing.</p>',
        'packages' => [
            ['basic', 'Corte comercial', 'Até 30s, uma versão.', 120000, 5, 1, 1, ['Até 30 segundos', 'Cor e corte', '1 revisão']],
            ['standard', 'Campanha curta', 'Até 60s + recorte para stories.', 180000, 7, 2, 1, ['Versão vertical', 'Legendas', '2 revisões']],
            ['premium', 'Pacote de campanha', 'Peça principal e 3 cortes.', 280000, 10, 3, 3, ['3 cortes extras', 'Trilha', '3 revisões']],
        ],
        'extras' => [
            ['Entrega em 24 horas', 'Sujeito a material já organizado.', 70000, 0],
            ['Legendas em inglês', 'Tradução simples das falas principais.', 25000, 1],
        ],
        'faqs' => [
            ['Vocês gravam?', 'Este serviço é edição. Gravação pode ser orçada à parte pelo chat.'],
        ],
        'gallery' => ['images/svc-edit.jpg', 'images/cat-video.jpg'],
        'sub' => 'edic-ao',
    ],
];

foreach ($details as $slug => $data) {
    $service = $pdo->query('SELECT id, category_id FROM services WHERE slug = ' . $pdo->quote($slug) . ' LIMIT 1')->fetch();
    if (!$service) {
        continue;
    }
    $id = (int) $service['id'];
    $subId = $pdo->prepare('SELECT id FROM subcategories WHERE category_id = :c AND slug = :s LIMIT 1');
    $subId->execute(['c' => $service['category_id'], 's' => $data['sub']]);
    $subcategoryId = $subId->fetchColumn() ?: null;

    $pdo->prepare('UPDATE services SET description = :d, subcategory_id = :sub, updated_at = :u WHERE id = :id')
        ->execute(['d' => $data['description'], 'sub' => $subcategoryId, 'u' => $now, 'id' => $id]);

    $pdo->prepare('DELETE FROM service_packages WHERE service_id = :id')->execute(['id' => $id]);
    $insPkg = $pdo->prepare(
        'INSERT INTO service_packages (service_id, tier, name, description, price_cents, delivery_days, revisions, quantity, benefits, is_active, created_at, updated_at)
         VALUES (:service_id, :tier, :name, :description, :price_cents, :delivery_days, :revisions, :quantity, :benefits, 1, :created_at, :updated_at)'
    );
    foreach ($data['packages'] as $pkg) {
        $insPkg->execute([
            'service_id' => $id,
            'tier' => $pkg[0],
            'name' => $pkg[1],
            'description' => $pkg[2],
            'price_cents' => $pkg[3],
            'delivery_days' => $pkg[4],
            'revisions' => $pkg[5],
            'quantity' => $pkg[6],
            'benefits' => json_encode($pkg[7], JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    $pdo->prepare('DELETE FROM service_extras WHERE service_id = :id')->execute(['id' => $id]);
    $insEx = $pdo->prepare(
        'INSERT INTO service_extras (service_id, name, description, price_cents, extra_days, is_active, created_at, updated_at)
         VALUES (:service_id, :name, :description, :price_cents, :extra_days, 1, :created_at, :updated_at)'
    );
    foreach ($data['extras'] as $ex) {
        $insEx->execute([
            'service_id' => $id,
            'name' => $ex[0],
            'description' => $ex[1],
            'price_cents' => $ex[2],
            'extra_days' => $ex[3],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    $pdo->prepare('DELETE FROM service_faqs WHERE service_id = :id')->execute(['id' => $id]);
    $insFaq = $pdo->prepare('INSERT INTO service_faqs (service_id, question, answer, sort_order, created_at) VALUES (:s, :q, :a, :o, :c)');
    $n = 1;
    foreach ($data['faqs'] as $faq) {
        $insFaq->execute(['s' => $id, 'q' => $faq[0], 'a' => $faq[1], 'o' => $n, 'c' => $now]);
        $n++;
    }

    $pdo->prepare('DELETE FROM service_images WHERE service_id = :id')->execute(['id' => $id]);
    $insImg = $pdo->prepare('INSERT INTO service_images (service_id, path, alt_text, sort_order, created_at) VALUES (:s, :p, :a, :o, :c)');
    $n = 1;
    foreach ($data['gallery'] as $path) {
        $insImg->execute(['s' => $id, 'p' => $path, 'a' => '', 'o' => $n, 'c' => $now]);
        $n++;
    }
}

echo "Detalhes de serviço aplicados.\n";
