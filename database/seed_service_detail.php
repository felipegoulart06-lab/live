<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$pdo = \App\Core\Database::pdo();
$now = date('Y-m-d H:i:s');

$skills = ['Vídeo institucional', 'UGC', 'Edição de vídeo', 'Locução', 'Treinamento interno', 'Produto', 'Roteiro', 'On camera'];
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

$sellerBios = [
    'ana-freire' => 'Apareço em câmera para institucional. Luz de janela, fala pausada. O tema sai do briefing da empresa.',
    'lucas-nunes' => 'Gravo treinamento olhando para a lente, como se o time estivesse na sala. Roteiro de vocês.',
    'rafael-moura' => 'Editor que também entra em quadro quando falta apresentador. Corte limpo, rosto nítido.',
    'priscila-costa' => 'UGC de conversa: eu na frente da tela, tom de amiga da marca, assunto definido por quem paga.',
    'ian-ramos' => 'Comercial e VSL com o rosto preenchendo o quadro. CTA e oferta vêm da empresa.',
    'jonathan-silva' => 'Propaganda em close. Eu falo para a câmera o recado que vocês escreverem.',
    'jamerson-alves' => 'Institucional sério, camisa lisa, fundo neutro. Horas de vídeo com o tema de vocês.',
    'andressa-vieira' => 'Mostro produto nas mãos e o meu rosto no mesmo plano. Demonstração no tom combinado.',
    'luan-rocha' => 'Comercial empresarial em talking head. Sem telefone no anúncio; o briefing chega no pedido.',
    'bruna-souza' => 'Vídeo de produto com apresentadora em close. Vocês definem o que eu digo e o que aparece.',
    'igor-lima' => 'Estilo natural, câmera na altura dos olhos. Parece conversa, não estúdio.',
    'eduarda-dias' => 'Serviço da empresa explicado por mim, de frente para a lente, com o roteiro interno de vocês.',
    'leticia-campos' => 'Capto em câmera e edito o close. Legendado se a empresa pedir no tema.',
    'ricardo-santos' => 'Locução com imagem: meu rosto em quadro enquanto leio o texto enviado.',
    'monique-ferreira' => 'Voz e imagem juntas. Close no rosto, fundo simples, tema da marca.',
    'daniel-duarte' => 'Cobertura de evento com takes de apresentador em câmera quando a empresa pedir.',
    'camile-vaz' => 'Onboarding em talking head: eu explico o processo que vocês descreverem, olhando para a tela.',
    'tiago-dias' => 'TV corporativa: apresentador de frente, recado do dia escrito pela empresa.',
];

$hours = [
    [2, 'hours_2', 1.00, 4, 1],
    [4, 'hours_4', 1.85, 7, 2],
    [6, 'hours_6', 2.65, 10, 3],
    [8, 'hours_8', 3.40, 14, 4],
];

$stories = [
    'horas-ugc-empresa' => [
        'lead' => 'Eu fico na frente da câmera, como uma conversa no celular, e falo o recado que a sua empresa mandar.',
        'use' => 'lançamento, prova social e anúncio em formato UGC',
        'look' => 'Close no rosto, luz natural, enquadramento vertical e horizontal',
    ],
    'horas-comercial-vsl' => [
        'lead' => 'Rosto preenchendo o quadro, fala direta para a lente, no ritmo de comercial ou VSL.',
        'use' => 'oferta, funil e página de vendas',
        'look' => 'Plano médio do peito para cima, fundo limpo, olhar na câmera',
    ],
    'propaganda-briefing-empresa' => [
        'lead' => 'Eu apareço em close e leio o briefing de vocês como se estivesse falando com o cliente da marca.',
        'use' => 'propaganda de produto ou serviço',
        'look' => 'Talking head, gestos curtos, produto só se vocês pedirem no tema',
    ],
    'institucional-horas' => [
        'lead' => 'Apresentador de frente para a empresa: tom institucional, camisa lisa, recado de cultura ou posicionamento.',
        'use' => 'site, recepção e onboarding de marca',
        'look' => 'Rosto nítido, fundo neutro, fala pausada',
    ],
    'demo-produto-horas' => [
        'lead' => 'Meu rosto e as mãos no mesmo vídeo: mostro o que vocês vendem e falo o benefício do briefing.',
        'use' => 'demonstração de produto ou app',
        'look' => 'Alternância de close no rosto e plano das mãos',
    ],
    'comercial-empresa-horas' => [
        'lead' => 'Comercial com apresentador em câmera. O assunto é o que a diretoria escrever no pedido.',
        'use' => 'campanha interna ou externa',
        'look' => 'Close cinematográfico, olhar firme na lente',
    ],
    'horas-produto-servico' => [
        'lead' => 'Eu olho para a tela e apresento o produto ou o serviço no tom que a empresa escolher.',
        'use' => 'loja, site e redes',
        'look' => 'Rosto em primeiro plano, produto ao lado se o briefing pedir',
    ],
    'horas-video-natural' => [
        'lead' => 'Câmera na altura dos olhos, como se eu estivesse no sofá falando com o cliente de vocês.',
        'use' => 'marca com tom informal',
        'look' => 'Close natural, pouco corte, presença contínua do rosto',
    ],
    'horas-servico-empresa' => [
        'lead' => 'Explico o serviço da empresa olhando para a câmera, no ritmo de quem atende o cliente.',
        'use' => 'página de serviço e treinamento comercial',
        'look' => 'Talking head, expressão aberta, fundo simples',
    ],
    'edicao-horas-empresa' => [
        'lead' => 'Se vocês já tiverem takes de rosto, eu corto o close e deixo a fala no tempo certo. Também gravo apresentador se faltar.',
        'use' => 'material interno que precisa de cara na tela',
        'look' => 'Prioridade no enquadramento do rosto e legendas',
    ],
    'locucao-horas-empresa' => [
        'lead' => 'Locução com imagem: meu rosto em quadro enquanto leio o texto que a empresa enviar.',
        'use' => 'vídeo institucional com off visível',
        'look' => 'Close estável, boca e olhos nítidos para a câmera',
    ],
    'horas-anuncio-lancamento' => [
        'lead' => 'Anúncio de lançamento com o meu rosto vendendo a oferta que vocês escreverem.',
        'use' => 'lançamento e mídia paga',
        'look' => 'Rosto em close, energia de campanha, CTA falado',
    ],
    'comercial-produto-horas' => [
        'lead' => 'Comercial de produto apresentado por mim, de frente, com o benefício do briefing.',
        'use' => 'varejo e e-commerce',
        'look' => 'Rosto + produto, sempre com a apresentadora visível',
    ],
    'voz-imagem-empresa' => [
        'lead' => 'Voz e imagem no mesmo take: eu na frente da câmera, lendo ou improvisando o tema de vocês.',
        'use' => 'marca que precisa de apresentadora',
        'look' => 'Close iluminado, fundo claro, contato visual constante',
    ],
    'horas-treinamento-interno' => [
        'lead' => 'Treinamento como aula: eu olho para a lente e explico o processo que o RH enviar.',
        'use' => 'onboarding e reciclagem de equipe',
        'look' => 'Plano peito e cabeça, como videochamada profissional',
    ],
    'apresentacao-institucional-horas' => [
        'lead' => 'Apresentação institucional com o meu rosto como porta-voz da marca, no texto de vocês.',
        'use' => 'quem somos e cultura',
        'look' => 'Talking head sóbrio, olhar na câmera, fundo discreto',
    ],
    'edicao-material-interno' => [
        'lead' => 'Edito horas em que o time já aparece em câmera e, se faltar apresentador, gravo o close.',
        'use' => 'vídeos internos com cara na tela',
        'look' => 'Corte no melhor take de rosto, legendas sob a fala',
    ],
    'cobertura-evento-horas' => [
        'lead' => 'No evento, quando a empresa pedir, eu fico de frente para a câmera e apresento o recado do palco.',
        'use' => 'feira, convenção, loja',
        'look' => 'Rosto em primeiro plano + takes do ambiente que vocês autorizarem',
    ],
    'onboarding-video-horas' => [
        'lead' => 'Onboarding falando com o colaborador novo: eu na tela, slides só se o briefing mandar.',
        'use' => 'primeiro dia do time',
        'look' => 'Câmera na altura dos olhos, como uma call gravada',
    ],
    'tv-corporativa-horas' => [
        'lead' => 'Apresentador de TV interna: rosto em close, recado do dia escrito pela empresa.',
        'use' => 'telas de loja, fábrica e escritório',
        'look' => 'Enquadramento 16:9, apresentador centralizado',
    ],
    'depoimento-roteiro-empresa' => [
        'lead' => 'Depoimento em câmera com o roteiro de vocês. Eu interpreto o recado, sem inventar contato pessoal.',
        'use' => 'prova social e case',
        'look' => 'Close íntimo, fala para a lente',
    ],
    'horas-fabrica-loja' => [
        'lead' => 'Bastidores com apresentador: eu falo para a câmera o que pode aparecer, segundo o briefing.',
        'use' => 'fábrica, loja, operação',
        'look' => 'Rosto em primeiro plano e recortes do espaço autorizado',
    ],
    'reels-tema-empresa' => [
        'lead' => 'Reels com o meu rosto ocupando a tela. O tema, a fala e o CTA saem do pedido da empresa.',
        'use' => 'redes em formato vertical',
        'look' => 'Close vertical, olhos na câmera, fundo simples',
    ],
    'anuncio-briefing-horas' => [
        'lead' => 'Anúncio gravado em talking head a partir do briefing: oferta, público e tom definidos por quem paga.',
        'use' => 'mídia paga',
        'look' => 'Rosto grande no quadro, fala objetiva',
    ],
    'edicao-evento-interno' => [
        'lead' => 'Edição que privilegia os takes de rosto do evento interno. Se precisar, gravo apresentador extra.',
        'use' => 'resumo de convenção',
        'look' => 'Close dos falantes, legendas, ritmo de TV',
    ],
    'demo-real-empresa' => [
        'lead' => 'Demonstração real com o meu rosto apresentando e as mãos no produto ou no app de vocês.',
        'use' => 'demo comercial',
        'look' => 'Rosto + tela ou produto, sempre com apresentadora visível',
    ],
    'processo-operacao-horas' => [
        'lead' => 'Processo da operação explicado por mim, olhando para a câmera, passo a passo do roteiro interno.',
        'use' => 'qualidade, segurança, atendimento',
        'look' => 'Talking head didático, como instrutor na tela',
    ],
    'locucao-institucional-horas' => [
        'lead' => 'Locução institucional com imagem: o público vê o meu rosto enquanto ouve o texto da empresa.',
        'use' => 'filme institucional',
        'look' => 'Close estável, dicção clara, olhar na lente',
    ],
];

$reviewPool = [
    ['Carla M.', 'Indústria alimentícia', 'Contratamos 4 horas. Ela olhou para a câmera o tempo todo e leu o tema do lançamento sem pedir telefone.'],
    ['Roberto S.', 'Rede de clínicas', 'O close ficou nítido. Mandamos o roteiro interno e recebemos o talking head no prazo.'],
    ['Fernanda L.', 'E-commerce', 'Compramos 2 horas de UGC. O rosto na tela vendeu melhor do que take de produto sozinho.'],
    ['Paulo H.', 'Software B2B', 'VSL com apresentador de frente. O CTA era nosso. Sem WhatsApp no anúncio, combinamos aqui.'],
    ['Juliana P.', 'Varejo', 'Treinamento em câmera para o time. Parecia uma call gravada, do jeito que pedimos.'],
    ['André C.', 'Construtora', 'Institucional sóbrio, rosto no centro. O tema de cultura veio do RH.'],
    ['Camila R.', 'Educação', 'Onboarding com apresentadora olhando para a lente. Os novos colaboradores entenderam o processo.'],
    ['Thiago N.', 'Logística', 'TV corporativa: recado do dia com o apresentador em close nas telas da operação.'],
];

$insPkg = $pdo->prepare(
    'INSERT INTO service_packages (service_id, tier, name, description, price_cents, delivery_days, revisions, quantity, benefits, is_active, created_at, updated_at)
     VALUES (:service_id, :tier, :name, :description, :price_cents, :delivery_days, :revisions, :quantity, :benefits, 1, :created_at, :updated_at)'
);
$insEx = $pdo->prepare(
    'INSERT INTO service_extras (service_id, name, description, price_cents, extra_days, is_active, created_at, updated_at)
     VALUES (:service_id, :name, :description, :price_cents, :extra_days, 1, :created_at, :updated_at)'
);
$insFaq = $pdo->prepare('INSERT INTO service_faqs (service_id, question, answer, sort_order, created_at) VALUES (:s, :q, :a, :o, :c)');
$insImg = $pdo->prepare('INSERT INTO service_images (service_id, path, alt_text, sort_order, created_at) VALUES (:s, :p, :a, :o, :c)');
$insRev = $pdo->prepare(
    'INSERT INTO service_reviews (service_id, author_name, company_name, rating, body, created_at)
     VALUES (:service_id, :author_name, :company_name, :rating, :body, :created_at)'
);

$faceFiles = [];
for ($i = 1; $i <= 23; $i++) {
    $faceFiles[] = 'images/face-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '.jpg';
}

$sql = "SELECT s.id, s.slug, s.title, s.short_description, s.starting_price_cents, s.cover_path, s.user_id, s.orders_count,
               p.slug AS seller_slug, p.display_name, p.avatar_path, c.name AS category_name
        FROM services s
        INNER JOIN profiles p ON p.user_id = s.user_id
        INNER JOIN categories c ON c.id = s.category_id
        WHERE s.status = 'published'";

$index = 0;
foreach ($pdo->query($sql) as $row) {
    $id = (int) $row['id'];
    $slug = (string) $row['slug'];
    $first = explode(' ', (string) $row['display_name'])[0];
    $story = $stories[$slug] ?? [
        'lead' => 'Eu gravo de frente para a câmera as horas que a empresa contratar. O tema é de quem paga.',
        'use' => 'vídeo corporativo com apresentador em tela',
        'look' => 'Talking head, rosto nítido, fundo simples',
    ];
    $base = max(18000, (int) $row['starting_price_cents']);
    $cover = (string) ($row['avatar_path'] ?: $row['cover_path'] ?: $faceFiles[$index % count($faceFiles)]);
    $stillA = $faceFiles[($index + 7) % count($faceFiles)];
    $stillB = $faceFiles[($index + 14) % count($faceFiles)];

    $description = '<h2>Como eu apareço no vídeo</h2>'
        . '<p>' . htmlspecialchars($story['lead'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
        . '<p>Este anúncio é de <strong>horas de vídeo em câmera</strong>: o quadro mostra o rosto de ' . htmlspecialchars($first, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . ' falando com a lente. Não é um pacote de edição anônima nem um take só de produto.</p>'
        . '<h2>O que a empresa define</h2><ul>'
        . '<li>O tema, o tom e o texto (ou o improviso autorizado)</li>'
        . '<li>O uso: ' . htmlspecialchars($story['use'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>'
        . '<li>Quantas horas: 2, 4, 6 ou 8</li>'
        . '</ul>'
        . '<h2>Enquadramento</h2><p>' . htmlspecialchars($story['look'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '. Luz de frente, olhos visíveis, sem telefone ou WhatsApp no anúncio público.</p>'
        . '<h3>Como funciona o pedido</h3><ol>'
        . '<li>A empresa descreve o tema neste anúncio</li>'
        . '<li>' . htmlspecialchars($first, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ' grava olhando para a câmera</li>'
        . '<li>A entrega das horas contratadas fica na plataforma</li>'
        . '</ol>';

    $pdo->prepare('UPDATE services SET description = :d, cover_path = :c, starting_price_cents = :p, updated_at = :u WHERE id = :id')
        ->execute(['d' => $description, 'c' => $cover, 'p' => $base, 'u' => $now, 'id' => $id]);

    $pdo->prepare('DELETE FROM service_packages WHERE service_id = :id')->execute(['id' => $id]);
    foreach ($hours as [$qty, $tier, $factor, $days, $revs]) {
        $insPkg->execute([
            'service_id' => $id,
            'tier' => $tier,
            'name' => $qty . ' horas em câmera',
            'description' => $qty . ' horas com o apresentador de frente para a tela. Tema definido pela empresa.',
            'price_cents' => (int) round($base * $factor),
            'delivery_days' => $days,
            'revisions' => $revs,
            'quantity' => $qty,
            'benefits' => json_encode([
                $qty . ' horas de talking head',
                'Rosto em quadro o tempo combinado',
                'Tema definido por quem paga',
                $revs . ' revisão(ões) no close',
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    $pdo->prepare('DELETE FROM service_extras WHERE service_id = :id')->execute(['id' => $id]);
    foreach ([
        ['+2 horas em câmera', 'Mais close com o mesmo tema.', (int) round($base * 0.9), 2],
        ['Versão vertical do rosto', 'Corte 9:16 com o apresentador centralizado.', (int) round($base * 0.25), 1],
        ['Legendas no close', 'Fala legendada sem expor contato.', (int) round($base * 0.2), 1],
    ] as $ex) {
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

    $faqs = [
        ['O vídeo mostra o rosto?', 'Sim. O formato padrão é talking head: apresentador olhando para a câmera. Produto só entra se o tema da empresa pedir.'],
        ['Quem define o que eu falo?', 'A empresa que paga. Vocês enviam o tema e o texto (ou autorizam improviso) no pedido.'],
        ['Vou ver telefone ou WhatsApp?', 'Não. O anúncio público não publica contato. A conversa e o briefing ficam na plataforma.'],
        ['Posso pedir outro enquadramento?', 'Sim. Close, plano médio ou vertical. O rosto continua no quadro.'],
    ];
    $pdo->prepare('DELETE FROM service_faqs WHERE service_id = :id')->execute(['id' => $id]);
    $n = 1;
    foreach ($faqs as $faq) {
        $insFaq->execute(['s' => $id, 'q' => $faq[0], 'a' => $faq[1], 'o' => $n, 'c' => $now]);
        $n++;
    }

    $pdo->prepare('DELETE FROM service_images WHERE service_id = :id')->execute(['id' => $id]);
    $n = 1;
    foreach ([
        [$cover, 'Apresentador em close, olhando para a câmera'],
        [$stillA, 'Segundo take em talking head'],
        [$stillB, 'Plano médio com o rosto no quadro'],
    ] as $img) {
        $insImg->execute(['s' => $id, 'p' => $img[0], 'a' => $img[1], 'o' => $n, 'c' => $now]);
        $n++;
    }

    $pdo->prepare('DELETE FROM service_reviews WHERE service_id = :id')->execute(['id' => $id]);
    if ((int) $row['orders_count'] > 0) {
        for ($r = 0; $r < 3; $r++) {
            $rev = $reviewPool[($index + $r) % count($reviewPool)];
            $insRev->execute([
                'service_id' => $id,
                'author_name' => $rev[0],
                'company_name' => $rev[1],
                'rating' => 5,
                'body' => $rev[2],
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . (8 + $r * 11) . ' days')),
            ]);
        }
    }

    $sellerSlug = (string) $row['seller_slug'];
    if (isset($sellerBios[$sellerSlug])) {
        $pdo->prepare('UPDATE profiles SET bio = :bio, experience_years = COALESCE(experience_years, 6), response_rate = COALESCE(response_rate, 97), avg_response_minutes = COALESCE(avg_response_minutes, 35), updated_at = :u WHERE user_id = :id')
            ->execute(['bio' => $sellerBios[$sellerSlug], 'u' => $now, 'id' => (int) $row['user_id']]);
    }

    $index++;
}

echo "Páginas de detalhe em talking head aplicadas.\n";
