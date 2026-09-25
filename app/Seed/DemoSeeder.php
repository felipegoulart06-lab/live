<?php

declare(strict_types=1);

namespace App\Seed;

use App\Core\Db;
use App\Services\Accounts;
use App\Services\Deals;
use App\Services\Listings;
use App\Services\Settings;

/**
 * Demo marketplace: 1 master admin, 1 staff admin, 10 creators, 10 companies, 20 listings and the
 * requests, contracts, payments, messages and reviews that follow from them. Deterministic (fixed seed).
 */
final class DemoSeeder
{
    private string $hash;
    private int $now;

    public function run(): void
    {
        mt_srand(50);
        $this->now = time();
        $this->hash = password_hash((string) env('SEED_USER_PASSWORD', 'Demo12345'), PASSWORD_DEFAULT);

        Db::transaction(function (): void {
            $this->settings();
            $categories = $this->categories();
            [$master, $staff] = $this->admins();
            $creators = $this->creators();
            $companies = $this->companies();
            $listings = $this->listings($creators, $categories, $master);
            $this->deals($listings, $companies, $master);
            $this->content($creators, $listings, $categories);
            $this->extras($companies, $creators, $listings, $master, $staff);
        });
    }

    private function ago(int $days, int $hours = 0): string
    {
        return date('Y-m-d H:i:s', $this->now - $days * 86400 - $hours * 3600);
    }

    private function settings(): void
    {
        $values = [];
        foreach (Settings::DEFAULTS as $key => [$value]) {
            $values[$key] = $value;
        }
        Settings::putMany($values);
    }

    /** @return array<string, int> slug => id */
    private function categories(): array
    {
        $rows = [
            ['UGC para marcas', 'Vídeos em formato de conversa, gravados como se fosse no celular, para anúncios e redes.', 'images/cat-video.jpg'],
            ['Institucional', 'Apresentador de frente para a câmera falando da empresa, da cultura ou de um posicionamento.', 'images/cat-business.jpg'],
            ['Treinamento interno', 'Onboarding, processos e comunicados para o time, gravados com o roteiro da empresa.', 'images/cat-writing.jpg'],
            ['Demonstração de produto', 'Produto nas mãos e rosto no mesmo plano, explicando uso e benefícios.', 'images/cat-shop.jpg'],
            ['Comercial e VSL', 'Oferta direta para a câmera, no ritmo de comercial ou página de vendas.', 'images/cat-marketing.jpg'],
            ['Depoimento', 'Relato em primeira pessoa com o tom e os pontos que a empresa definir.', 'images/cat-photo.jpg'],
            ['Evento corporativo', 'Cobertura de evento com apresentador em quadro e chamadas ao vivo.', 'images/cat-arch.jpg'],
            ['Locução com imagem', 'Leitura do texto da empresa com o rosto do criador em quadro.', 'images/cat-audio.jpg'],
        ];
        $ids = [];
        foreach ($rows as $i => [$name, $description, $image]) {
            $ids[slugify($name)] = Db::insert('categories', [
                'name' => $name,
                'slug' => slugify($name),
                'description' => $description,
                'image_path' => $image,
                'status' => 'visible',
                'sort_order' => $i,
                'created_at' => $this->ago(200),
                'updated_at' => $this->ago(200),
            ]);
        }

        return $ids;
    }

    /** @return array{0: int, 1: int} */
    private function admins(): array
    {
        $master = Accounts::create('admin', [
            'display_name' => 'Administração CinquentaConto',
            'email' => (string) env('SEED_ADMIN_EMAIL', 'admin@cinquentaconto.com.br'),
            'password' => (string) env('SEED_ADMIN_PASSWORD', 'CinquentaAdmin!234'),
            'email_verified_at' => $this->ago(210),
            'created_at' => $this->ago(210),
        ], 'master');
        $staff = Accounts::create('admin', [
            'display_name' => 'Moderação',
            'email' => 'moderacao@cinquentaconto.com.br',
            'password_hash' => $this->hash,
            'email_verified_at' => $this->ago(150),
            'created_at' => $this->ago(150),
        ], 'staff');

        return [$master, $staff];
    }

    /** @return array<int, array<string, mixed>> */
    private function creators(): array
    {
        $rows = [
            ['Ana Freire', 'ana.freire', 'São Paulo', 'SP', 'Apresentadora para institucional e treinamento', 'Apareço em câmera para institucional. Luz de janela, fala pausada e o tema saindo do briefing da empresa. Gravo em estúdio próprio com fundo neutro e também vou até a empresa quando o roteiro pede.', 7, 'Institucional, treinamento, onboarding', 'images/face-01.jpg', 1],
            ['Lucas Nunes', 'lucas.nunes', 'Belo Horizonte', 'MG', 'Treinamento interno olhando para a lente', 'Gravo treinamento como se o time estivesse na sala. Transformo processo em fala simples, em blocos curtos que o RH consegue reaproveitar. O roteiro é de vocês; eu ajusto o ritmo.', 5, 'Treinamento, processos, RH', 'images/face-02.jpg', 1],
            ['Priscila Costa', 'priscila.costa', 'Curitiba', 'PR', 'UGC de conversa para anúncios', 'UGC com cara de conversa entre amigas. Gravo na vertical e na horizontal, com cortes pensados para anúncio. A empresa define o produto, a promessa e o que não pode ser dito.', 4, 'UGC, redes sociais, anúncios', 'images/face-03.jpg', 1],
            ['Rafael Moura', 'rafael.moura', 'Rio de Janeiro', 'RJ', 'Comercial e VSL em talking head', 'Rosto preenchendo o quadro, fala direta e ritmo de comercial. Trabalho bem com roteiro de oferta e chamada para ação. Entrego o bruto organizado e a versão editada.', 8, 'Comercial, VSL, oferta', 'images/face-04.jpg', 1],
            ['Bruna Souza', 'bruna.souza', 'Porto Alegre', 'RS', 'Demonstração de produto com apresentadora', 'Produto nas mãos e rosto no mesmo plano. Mostro o uso passo a passo e falo dos benefícios no tom que a marca escolher. Tenho mesa de gravação para cosméticos, eletrônicos e alimentos.', 6, 'Produto, e-commerce, unboxing', 'images/face-05.jpg', 1],
            ['Igor Lima', 'igor.lima', 'Recife', 'PE', 'Estilo natural, câmera na altura dos olhos', 'Vídeo que parece conversa e não estúdio. Bom para marcas que querem proximidade. Gravo depoimentos e explicações curtas, sempre com o texto aprovado pela empresa.', 3, 'Depoimento, UGC, proximidade', 'images/face-06.jpg', 0],
            ['Eduarda Dias', 'eduarda.dias', 'Florianópolis', 'SC', 'Serviços explicados de frente para a lente', 'Explico o serviço da empresa com clareza, de frente para a câmera. Divido o conteúdo em vídeos curtos para site, onboarding de clientes e redes.', 5, 'Serviços, onboarding de clientes', 'images/face-07.jpg', 1],
            ['Ricardo Santos', 'ricardo.santos', 'Salvador', 'BA', 'Locução com imagem e leitura de roteiro', 'Leio o texto da empresa com o rosto em quadro e voz de locutor. Gravo com teleprompter, o que deixa o olhar sempre na lente, mesmo em roteiros longos.', 12, 'Locução, teleprompter, TV corporativa', 'images/face-08.jpg', 1],
            ['Camile Vaz', 'camile.vaz', 'Goiânia', 'GO', 'Onboarding e comunicados internos', 'Onboarding em talking head: explico o processo que vocês descreverem, com calma e clareza. Também gravo comunicados de liderança quando o gestor não pode aparecer.', 4, 'Onboarding, comunicação interna', 'images/face-09.jpg', 0],
            ['Tiago Dias', 'tiago.dias', 'Campinas', 'SP', 'Apresentador para eventos e TV corporativa', 'Apresentador de frente, recado do dia escrito pela empresa. Faço cobertura de eventos com chamadas em quadro e entrevistas rápidas com convidados.', 9, 'Eventos, TV corporativa, entrevistas', 'images/face-10.jpg', 1],
        ];
        $creators = [];
        foreach ($rows as $i => [$name, $mail, $city, $state, $headline, $bio, $years, $specialties, $avatar, $verified]) {
            $created = $this->ago(190 - $i * 9);
            $id = Accounts::create('creator', [
                'display_name' => $name,
                'email' => $mail . '@criador.demo',
                'password_hash' => $this->hash,
                'avatar_path' => $avatar,
                'city' => $city,
                'state' => $state,
                'headline' => $headline,
                'bio' => $bio,
                'experience_years' => $years,
                'specialties' => $specialties,
                'is_verified' => $verified,
                'email_verified_at' => $created,
                'created_at' => $created,
            ]);
            Db::update('users', ['last_seen_at' => $this->ago(mt_rand(0, 6), mt_rand(0, 20))], ['id' => $id]);
            Db::update('creators', ['min_notice_hours' => [24, 48, 72][$i % 3], 'created_at' => $created], ['user_id' => $id]);
            foreach ([1, 2, 3, 4, 5] as $weekday) {
                if ($i % 4 === 0 && $weekday === 5) {
                    continue;
                }
                Db::insert('availability', ['creator_id' => $id, 'weekday' => $weekday, 'start_time' => $i % 2 ? '09:00' : '08:00', 'end_time' => $i % 2 ? '18:00' : '17:00', 'created_at' => $created, 'updated_at' => $created]);
            }
            if ($i % 3 === 0) {
                Db::insert('availability', ['creator_id' => $id, 'weekday' => 6, 'start_time' => '09:00', 'end_time' => '13:00', 'created_at' => $created, 'updated_at' => $created]);
            }
            $creators[] = ['id' => $id, 'name' => $name, 'rate' => 18000 + $i * 2500];
        }
        Db::update('creators', ['unavailable_from' => date('Y-m-d', $this->now + 20 * 86400), 'unavailable_until' => date('Y-m-d', $this->now + 27 * 86400), 'unavailable_note' => 'Férias programadas'], ['user_id' => $creators[4]['id']]);
        Db::update('creators', ['verification_requested_at' => $this->ago(2)], ['user_id' => $creators[5]['id']]);

        return $creators;
    }

    /** @return array<int, array<string, mixed>> */
    private function companies(): array
    {
        $rows = [
            ['Marina Albuquerque', 'Casa Aurora Cosméticos', 'Casa Aurora Indústria de Cosméticos Ltda.', 'São Paulo', 'SP'],
            ['Paulo Henrique Reis', 'Rota Norte Logística', 'Rota Norte Transportes S.A.', 'Manaus', 'AM'],
            ['Juliana Prado', 'Clínica Vivace', 'Vivace Serviços Médicos Ltda.', 'Belo Horizonte', 'MG'],
            ['André Coutinho', 'Pixel Pay', 'Pixel Pay Instituição de Pagamento Ltda.', 'São Paulo', 'SP'],
            ['Fernanda Luz', 'Grão Nobre Cafés', 'Grão Nobre Torrefação Ltda.', 'Varginha', 'MG'],
            ['Roberto Tanaka', 'Tanaka Engenharia', 'Tanaka Engenharia e Construções Ltda.', 'Curitiba', 'PR'],
            ['Carla Menezes', 'EducaMais Cursos', 'EducaMais Ensino a Distância Ltda.', 'Recife', 'PE'],
            ['Diego Farias', 'Verde Vivo Orgânicos', 'Verde Vivo Alimentos Ltda.', 'Porto Alegre', 'RS'],
            ['Helena Brandão', 'Atlas Seguros', 'Atlas Corretora de Seguros Ltda.', 'Rio de Janeiro', 'RJ'],
            ['Gustavo Pires', 'Nuvem Nove Software', 'Nuvem Nove Tecnologia Ltda.', 'Florianópolis', 'SC'],
        ];
        $companies = [];
        foreach ($rows as $i => [$contact, $company, $legal, $city, $state]) {
            $created = $this->ago(170 - $i * 11);
            $id = Accounts::create('company', [
                'display_name' => $contact,
                'email' => 'contato@' . slugify($company) . '.demo',
                'password_hash' => $this->hash,
                'company_name' => $company,
                'legal_name' => $legal,
                'document' => sprintf('%02d.%03d.%03d/0001-%02d', 10 + $i, 100 + $i * 7, 200 + $i * 13, 10 + $i),
                'responsible_name' => $contact,
                'city' => $city,
                'state' => $state,
                'website' => 'https://' . slugify($company) . '.com.br',
                'email_verified_at' => $created,
                'created_at' => $created,
            ]);
            Db::update('users', ['last_seen_at' => $this->ago(mt_rand(0, 10))], ['id' => $id]);
            $companies[] = ['id' => $id, 'name' => $company];
        }

        return $companies;
    }

    /** @return array<int, array<string, mixed>> */
    private function listings(array $creators, array $categories, int $adminId): array
    {
        $specs = [
            [0, 'institucional', 'Vídeo institucional com apresentadora em estúdio', 'Apresento a sua empresa de frente para a câmera, com o roteiro e o tom que vocês definirem.'],
            [0, 'treinamento-interno', 'Treinamento de onboarding gravado com o roteiro do RH', 'Blocos curtos de onboarding para o novo colaborador assistir no primeiro dia.'],
            [1, 'treinamento-interno', 'Treinamento interno de processos em vídeo curto', 'Transformo o processo da empresa em aulas curtas, olhando para a lente.'],
            [1, 'depoimento', 'Depoimento de colaborador para marca empregadora', 'Relato em primeira pessoa sobre a rotina e a cultura, com os pontos que vocês aprovarem.'],
            [2, 'ugc-para-marcas', 'UGC de conversa para anúncios de cosméticos', 'Vídeo com cara de conversa no celular, pronto para anúncio, com o produto de vocês.'],
            [2, 'ugc-para-marcas', 'Pacote de UGC vertical para Reels e TikTok', 'Roteiros curtos em formato vertical, gravados no tom da marca.'],
            [3, 'comercial-e-vsl', 'VSL com apresentador falando direto para a câmera', 'Página de vendas em vídeo: oferta, prova e chamada para ação no ritmo certo.'],
            [3, 'comercial-e-vsl', 'Comercial de 30 segundos em talking head', 'Recado direto da marca em plano fechado, com versão de 15 e 30 segundos.'],
            [4, 'demonstracao-de-produto', 'Demonstração de produto com mãos e rosto em quadro', 'Mostro o produto em uso e explico os benefícios que a empresa escolher.'],
            [4, 'demonstracao-de-produto', 'Unboxing e primeiras impressões para e-commerce', 'Abro a embalagem em câmera e mostro os detalhes que vendem o produto.'],
            [5, 'depoimento', 'Depoimento natural de cliente para landing page', 'Relato espontâneo, câmera na altura dos olhos, com o texto aprovado pela marca.'],
            [5, 'ugc-para-marcas', 'UGC masculino para produtos de tecnologia', 'Conversa direta sobre o produto, sem cara de propaganda.'],
            [6, 'institucional', 'Explicação de serviços para o site da empresa', 'Explico o que a empresa faz em vídeos curtos para cada serviço do site.'],
            [6, 'treinamento-interno', 'Onboarding de clientes em vídeo passo a passo', 'Mostro para o cliente como começar a usar o serviço, sem jargão.'],
            [7, 'locucao-com-imagem', 'Locução com imagem para TV corporativa', 'Leio o texto da empresa com o rosto em quadro e voz de locutor, com teleprompter.'],
            [7, 'institucional', 'Mensagem da diretoria gravada com teleprompter', 'Comunicado oficial lido com naturalidade, olhando para a lente o tempo todo.'],
            [8, 'treinamento-interno', 'Comunicados internos semanais em vídeo', 'Recados da liderança para o time, gravados toda semana no mesmo padrão.'],
            [8, 'depoimento', 'Série de depoimentos para campanha de endomarketing', 'Depoimentos curtos sobre valores da empresa, em série com identidade única.'],
            [9, 'evento-corporativo', 'Apresentador para evento corporativo e convenção', 'Conduzo o evento em quadro, com chamadas, entrevistas e encerramento.'],
            [9, 'locucao-com-imagem', 'Apresentador para TV corporativa e telejornal interno', 'Telejornal interno com escalada, notícias da empresa e encerramento.'],
        ];
        $statuses = ['active', 'active', 'active', 'active', 'active', 'active', 'active', 'active', 'active', 'active', 'active', 'active', 'active', 'active', 'active', 'pending', 'active', 'draft', 'rejected', 'paused'];
        $multipliers = [2 => 1.0, 4 => 1.85, 6 => 2.65, 8 => 3.4];
        $images = ['images/face-11.jpg', 'images/face-12.jpg', 'images/face-13.jpg', 'images/face-14.jpg', 'images/face-15.jpg', 'images/face-16.jpg', 'images/face-17.jpg', 'images/face-18.jpg', 'images/face-19.jpg', 'images/face-20.jpg', 'images/face-21.jpg', 'images/face-22.jpg', 'images/face-23.jpg', 'images/hero-studio.jpg', 'images/svc-brand.jpg', 'images/svc-edit.jpg'];

        $listings = [];
        foreach ($specs as $n => [$creatorIndex, $categorySlug, $title, $short]) {
            $creator = $creators[$creatorIndex];
            $status = $statuses[$n];
            $created = $this->ago(160 - $n * 6);
            $published = in_array($status, ['active', 'paused'], true) ? $this->ago(155 - $n * 6) : null;
            $uuid = uuid4();
            $description = $short . "\n\nAntes de gravar, a empresa envia o briefing com tema, mensagem principal, público e o que não pode aparecer. Eu devolvo um roteiro de gravação para aprovação e, com o ok de vocês, gravo em estúdio ou no local combinado.\n\nA entrega inclui os arquivos brutos organizados por take e a versão editada no formato combinado (horizontal, vertical ou ambos). Cada pacote de horas cobre preparação, gravação e ajustes dentro do número de revisões.";
            $id = Db::insert('listings', [
                'uuid' => $uuid,
                'creator_id' => $creator['id'],
                'category_id' => $categories[$categorySlug],
                'title' => $title,
                'slug' => Listings::uniqueSlug($title),
                'short_description' => $short,
                'description' => $description,
                'what_company_buys' => 'Horas de gravação com o criador em quadro, roteiro de gravação para aprovação, arquivos brutos e versão editada.',
                'what_company_provides' => 'Briefing com tema e mensagem principal, logotipo e materiais da marca, produto (quando houver) e aprovação do roteiro.',
                'how_it_works' => "1. A empresa escolhe o pacote e envia o briefing.\n2. O criador aceita e o contrato é criado.\n3. Após o pagamento, a gravação é agendada.\n4. Entrega dos arquivos e ajustes dentro das revisões.",
                'framing' => 'Plano médio ou close, olhar na lente, fundo neutro ou ambiente da empresa. Formatos 16:9 e 9:16.',
                'additional_info' => 'Gravações fora da cidade têm custo de deslocamento combinado à parte, antes do contrato.',
                'status' => $status,
                'rejection_reason' => $status === 'rejected' ? 'As fotos mostram um número de telefone na parede do estúdio. Troque as imagens por fotos sem dados de contato e envie de novo.' : null,
                'wizard_step' => $status === 'draft' ? 3 : 5,
                'submitted_at' => $status !== 'draft' ? $this->ago(158 - $n * 6) : null,
                'approved_at' => $published,
                'published_at' => $published,
                'views_count' => in_array($status, ['active', 'paused'], true) ? mt_rand(80, 900) : 0,
                'created_at' => $created,
                'updated_at' => $status === 'pending' ? $this->ago(1) : $created,
            ]);

            $base = $creator['rate'] + ($n % 3) * 1500;
            $offered = $status === 'draft' ? [2, 4] : ($n % 4 === 3 ? [2, 4, 6] : [2, 4, 6, 8]);
            foreach ($offered as $i => $hours) {
                Db::insert('listing_packages', [
                    'listing_id' => $id,
                    'hours' => $hours,
                    'price_cents' => (int) (round($base * $multipliers[$hours] / 1000) * 1000),
                    'delivery_days' => [2 => 3, 4 => 5, 6 => 7, 8 => 10][$hours],
                    'revisions' => min(3, $i + 1),
                    'description' => [2 => 'Um vídeo curto com um tema.', 4 => 'Até três vídeos curtos ou um vídeo médio.', 6 => 'Série de vídeos com o mesmo cenário.', 8 => 'Diária completa de gravação.'][$hours],
                    'sort_order' => $i,
                    'created_at' => $created,
                    'updated_at' => $created,
                ]);
            }
            $addons = [
                ['Legendas em português', 'Arquivo de legenda e versão com legenda embutida.', 9000, 1],
                ['Roteiro escrito pelo criador', 'Roteiro a partir do briefing, com uma rodada de ajustes.', 18000, 2],
                ['Entrega expressa', 'Entrega em metade do prazo do pacote.', 25000, 0],
                ['Versão vertical extra', 'Reenquadramento de todos os vídeos para 9:16.', 12000, 1],
            ];
            foreach (array_slice($addons, $n % 2, 2 + $n % 2) as $i => [$name, $desc, $price, $days]) {
                Db::insert('listing_addons', ['listing_id' => $id, 'name' => $name, 'description' => $desc, 'price_cents' => $price, 'extra_days' => $days, 'sort_order' => $i, 'created_at' => $created, 'updated_at' => $created]);
            }
            foreach ([
                ['Posso gravar na minha empresa?', 'Sim. Informe o endereço no briefing; deslocamentos fora da cidade são combinados antes do contrato.'],
                ['Quem escreve o roteiro?', 'A empresa envia o tema e a mensagem. Se preferir, contrate o adicional de roteiro.'],
            ] as $i => [$question, $answer]) {
                Db::insert('listing_faqs', ['listing_id' => $id, 'question' => $question, 'answer' => $answer, 'sort_order' => $i, 'created_at' => $created, 'updated_at' => $created]);
            }
            if ($status !== 'draft') {
                foreach ([0, 1, 2] as $i) {
                    Db::insert('listing_images', ['listing_id' => $id, 'path' => $images[($n + $i * 5) % count($images)], 'thumb_path' => null, 'alt_text' => $title, 'is_primary' => $i === 0 ? 1 : 0, 'sort_order' => $i, 'created_at' => $created]);
                }
                Db::update('listings', ['cover_path' => $images[$n % count($images)]], ['id' => $id]);
                Listings::logModeration($id, $creator['id'], 'submitted', null);
                if ($published) {
                    Listings::logModeration($id, $adminId, 'approved', null);
                }
                if ($status === 'rejected') {
                    Listings::logModeration($id, $adminId, 'rejected', 'As fotos mostram um número de telefone na parede do estúdio. Troque as imagens por fotos sem dados de contato e envie de novo.');
                }
                if ($status === 'paused') {
                    Listings::logModeration($id, $creator['id'], 'paused', null);
                }
            }
            Listings::refreshPricing($id);
            $listings[] = ['id' => $id, 'uuid' => $uuid, 'creator_id' => $creator['id'], 'status' => $status, 'title' => $title];
        }

        return $listings;
    }

    private function deals(array $listings, array $companies, int $adminId): void
    {
        $active = array_values(array_filter($listings, static fn (array $l): bool => $l['status'] === 'active'));
        $themes = [
            'Lançamento da nova linha de produtos',
            'Boas-vindas para novos colaboradores',
            'Campanha de fim de ano para clientes',
            'Explicação do novo processo de atendimento',
            'Depoimento sobre a cultura da empresa',
            'Anúncio da nova unidade',
            'Treinamento de segurança no trabalho',
            'Apresentação do aplicativo para clientes',
        ];
        $messages = [
            ['company', 'Oi! Vimos o seu anúncio e queremos gravar sobre %s. Vocês têm agenda nas próximas semanas?'],
            ['creator', 'Olá! Tenho sim. Pode me mandar o briefing com a mensagem principal e o público?'],
            ['company', 'Mandamos na solicitação. O principal é deixar claro o benefício em até 30 segundos.'],
            ['creator', 'Perfeito, ficou claro. Vou preparar o roteiro de gravação e envio aqui para aprovação.'],
            ['company', 'Ótimo, obrigado!'],
        ];

        // [status of request, contract status or null, days ago]
        $plan = [
            ['accepted', 'completed', 150], ['accepted', 'completed', 140], ['accepted', 'completed', 128], ['accepted', 'completed', 115],
            ['accepted', 'completed', 100], ['accepted', 'completed', 88], ['accepted', 'completed', 75], ['accepted', 'completed', 60],
            ['accepted', 'completed', 45], ['accepted', 'in_progress', 20], ['accepted', 'in_progress', 14], ['accepted', 'confirmed', 10],
            ['accepted', 'confirmed', 8], ['accepted', 'awaiting_payment', 4], ['accepted', 'awaiting_payment', 2], ['accepted', 'cancelled', 70],
            ['accepted', 'cancelled', 30], ['declined', null, 50], ['declined', null, 25], ['expired', null, 40],
            ['cancelled', null, 18], ['pending', null, 1], ['pending', null, 1], ['pending', null, 0],
        ];
        $fee = Settings::int('platform_fee_percent');
        $reviewTexts = [
            [5, 'Entregou antes do prazo e seguiu o briefing à risca. O vídeo já está no nosso site.'],
            [5, 'Muito profissional na gravação. Ajustou o texto na hora quando pedimos uma mudança.'],
            [4, 'Resultado ótimo. Só precisamos de uma revisão a mais no corte final.'],
            [5, 'O time adorou o treinamento. Linguagem simples e ritmo bom.'],
            [4, 'Gravação tranquila e bem organizada. Recomendamos.'],
            [5, 'Segunda vez que contratamos. Qualidade de estúdio e muita pontualidade.'],
            [3, 'O vídeo ficou bom, mas a entrega atrasou um dia em relação ao combinado.'],
            [5, 'Entendeu a marca de primeira. A campanha teve ótimo resultado.'],
        ];

        foreach ($plan as $i => [$requestStatus, $contractStatus, $days]) {
            $listing = $active[($i * 3) % count($active)];
            $company = $companies[$i % count($companies)];
            $package = Db::first('SELECT * FROM listing_packages WHERE listing_id = :l ORDER BY hours LIMIT 1 OFFSET ' . ($i % 3), ['l' => $listing['id']])
                ?? Db::first('SELECT * FROM listing_packages WHERE listing_id = :l ORDER BY hours LIMIT 1', ['l' => $listing['id']]);
            $addon = $i % 3 === 0 ? Db::first('SELECT id, name, price_cents, extra_days FROM listing_addons WHERE listing_id = :l ORDER BY sort_order LIMIT 1', ['l' => $listing['id']]) : null;
            $addonsCents = $addon ? (int) $addon['price_cents'] : 0;
            $total = (int) $package['price_cents'] + $addonsCents;
            $theme = $themes[$i % count($themes)];
            $createdAt = $this->ago($days, 3);

            $conversationId = Db::insert('conversations', [
                'uuid' => uuid4(),
                'company_id' => $company['id'],
                'creator_id' => $listing['creator_id'],
                'listing_id' => $listing['id'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            $count = $requestStatus === 'pending' ? 1 : 3 + $i % 3;
            $last = $createdAt;
            foreach (array_slice($messages, 0, $count) as $m => [$who, $text]) {
                $last = date('Y-m-d H:i:s', strtotime($createdAt) + $m * 5400);
                Db::insert('messages', [
                    'conversation_id' => $conversationId,
                    'sender_id' => $who === 'company' ? $company['id'] : $listing['creator_id'],
                    'body' => sprintf($text, mb_strtolower($theme)),
                    'created_at' => $last,
                ]);
            }
            $readByCreator = $requestStatus === 'pending' ? null : $last;
            Db::update('conversations', ['last_message_at' => $last, 'company_read_at' => $last, 'creator_read_at' => $readByCreator], ['id' => $conversationId]);

            $responded = $requestStatus === 'pending' || $requestStatus === 'expired' || $requestStatus === 'cancelled' ? null : date('Y-m-d H:i:s', strtotime($createdAt) + 7200);
            $requestId = Db::insert('requests', [
                'uuid' => uuid4(),
                'code' => 'SOL-' . date('ymd', strtotime($createdAt)) . '-' . strtoupper(substr(md5((string) $i), 0, 6)),
                'company_id' => $company['id'],
                'creator_id' => $listing['creator_id'],
                'listing_id' => $listing['id'],
                'package_id' => (int) $package['id'],
                'conversation_id' => $conversationId,
                'hours' => (int) $package['hours'],
                'package_price_cents' => (int) $package['price_cents'],
                'addons_cents' => $addonsCents,
                'total_cents' => $total,
                'addons_json' => $addon ? json_encode([$addon], JSON_UNESCAPED_UNICODE) : null,
                'desired_date' => date('Y-m-d', strtotime($createdAt) + (7 + $i % 5) * 86400),
                'desired_time' => ['09:00', '14:00', '10:30'][$i % 3],
                'theme' => $theme,
                'briefing' => 'Público: clientes e colaboradores. Tom: próximo e confiável. Mensagem principal: ' . mb_strtolower($theme) . '. Evitar citar preços e concorrentes.',
                'message' => sprintf($messages[0][1], mb_strtolower($theme)),
                'status' => $requestStatus,
                'decline_reason' => $requestStatus === 'declined' ? 'Não tenho agenda na data pedida. Posso gravar a partir da semana seguinte, se servir.' : null,
                'expires_at' => date('Y-m-d H:i:s', strtotime($createdAt) + 72 * 3600),
                'responded_at' => $responded,
                'created_at' => $createdAt,
                'updated_at' => $responded ?? $createdAt,
            ]);
            Db::run('UPDATE listings SET contacts_count = contacts_count + 1 WHERE id = :id', ['id' => $listing['id']]);

            if ($contractStatus === null) {
                continue;
            }

            $feeCents = (int) round($total * $fee / 100);
            $contractId = Db::insert('contracts', [
                'uuid' => uuid4(),
                'code' => 'CTR-' . date('ymd', strtotime((string) $responded)) . '-' . strtoupper(substr(md5('c' . $i), 0, 6)),
                'request_id' => $requestId,
                'company_id' => $company['id'],
                'creator_id' => $listing['creator_id'],
                'listing_id' => $listing['id'],
                'package_id' => (int) $package['id'],
                'conversation_id' => $conversationId,
                'hours' => (int) $package['hours'],
                'package_price_cents' => (int) $package['price_cents'],
                'addons_cents' => $addonsCents,
                'total_cents' => $total,
                'fee_cents' => $feeCents,
                'creator_amount_cents' => $total - $feeCents,
                'addons_json' => $addon ? json_encode([$addon], JSON_UNESCAPED_UNICODE) : null,
                'scheduled_date' => date('Y-m-d', strtotime($createdAt) + (7 + $i % 5) * 86400),
                'scheduled_time' => ['09:00', '14:00', '10:30'][$i % 3],
                'theme' => $theme,
                'briefing' => 'Público: clientes e colaboradores. Tom: próximo e confiável.',
                'status' => $contractStatus,
                'created_at' => $responded,
                'updated_at' => $responded,
            ]);
            Deals::event($contractId, $listing['creator_id'], 'created', null);
            Db::run('UPDATE contract_events SET created_at = :t WHERE contract_id = :c', ['t' => $responded, 'c' => $contractId]);

            $paymentStatus = 'pending';
            $paidAt = null;
            $step = strtotime((string) $responded);
            $stamp = static fn (int $plusDays) => date('Y-m-d H:i:s', $step + $plusDays * 86400);
            $contractUpdate = [];
            if (in_array($contractStatus, ['confirmed', 'in_progress', 'completed'], true) || ($contractStatus === 'cancelled' && $i === 16)) {
                $paidAt = $stamp(1);
                $paymentStatus = 'paid';
                $contractUpdate += ['paid_at' => $paidAt, 'confirmed_at' => $paidAt];
                $this->event($contractId, $adminId, 'payment_confirmed', 'pix · ref. E' . (100000 + $i), $paidAt);
            }
            if (in_array($contractStatus, ['in_progress', 'completed'], true)) {
                $contractUpdate['started_at'] = $stamp(5);
                $this->event($contractId, $listing['creator_id'], 'started', null, $stamp(5));
            }
            if ($contractStatus === 'completed') {
                $contractUpdate['completed_at'] = $stamp(9);
                $this->event($contractId, $listing['creator_id'], 'completed', 'Arquivos entregues na pasta combinada.', $stamp(9));
                $paymentStatus = $days > 70 ? 'paid_out' : 'awaiting_payout';
                Db::run('UPDATE creators SET contracts_count = contracts_count + 1, hours_sold = hours_sold + :h WHERE user_id = :u', ['h' => (int) $package['hours'], 'u' => $listing['creator_id']]);
                Db::run('UPDATE listings SET contracts_count = contracts_count + 1 WHERE id = :id', ['id' => $listing['id']]);
            }
            if ($contractStatus === 'cancelled') {
                $contractUpdate += ['cancelled_at' => $stamp(2), 'cancel_reason' => $i === 16 ? 'A empresa adiou a campanha por tempo indeterminado.' : 'A data não funcionou para as duas partes.'];
                $paymentStatus = $paidAt ? 'refunded' : 'cancelled';
                $this->event($contractId, $i === 16 ? $adminId : $company['id'], 'cancelled', $contractUpdate['cancel_reason'], $stamp(2));
            }
            if ($contractUpdate !== []) {
                $lastChange = $contractUpdate['cancelled_at'] ?? $contractUpdate['completed_at'] ?? $contractUpdate['started_at'] ?? $contractUpdate['paid_at'] ?? $responded;
                Db::update('contracts', $contractUpdate + ['updated_at' => $lastChange], ['id' => $contractId]);
            }

            Db::insert('payments', [
                'uuid' => uuid4(),
                'contract_id' => $contractId,
                'company_id' => $company['id'],
                'creator_id' => $listing['creator_id'],
                'amount_cents' => $total,
                'fee_cents' => $feeCents,
                'net_cents' => $total - $feeCents,
                'status' => $paymentStatus,
                'method' => $paidAt ? 'pix' : null,
                'gateway' => $paidAt ? 'manual' : null,
                'gateway_reference' => $paidAt ? 'E' . (100000 + $i) : null,
                'paid_at' => $paidAt,
                'paid_out_at' => $paymentStatus === 'paid_out' ? $stamp(16) : null,
                'cancelled_at' => $paymentStatus === 'cancelled' ? $stamp(2) : null,
                'refunded_at' => $paymentStatus === 'refunded' ? $stamp(2) : null,
                'created_at' => $responded,
                'updated_at' => $responded,
            ]);
            if ($paymentStatus === 'paid_out') {
                $this->event($contractId, $adminId, 'payout', 'TED ' . (500 + $i), $stamp(16));
            }

            if ($contractStatus === 'completed' && $i !== 8) {
                [$rating, $comment] = $reviewTexts[$i % count($reviewTexts)];
                Db::insert('reviews', [
                    'contract_id' => $contractId,
                    'listing_id' => $listing['id'],
                    'creator_id' => $listing['creator_id'],
                    'company_id' => $company['id'],
                    'rating' => $rating,
                    'comment' => $comment,
                    'status' => 'visible',
                    'created_at' => $stamp(11),
                    'updated_at' => $stamp(11),
                ]);
                $this->event($contractId, $company['id'], 'reviewed', $rating . ' de 5', $stamp(11));
                Deals::refreshRatings($listing['id'], $listing['creator_id']);
            }
        }
    }

    private function event(int $contractId, ?int $actorId, string $type, ?string $note, string $at): void
    {
        Db::insert('contract_events', ['contract_id' => $contractId, 'actor_id' => $actorId, 'type' => $type, 'note' => $note, 'created_at' => $at]);
    }

    private function content(array $creators, array $listings, array $categories): void
    {
        $faqs = [
            ['Como funciona a contratação?', 'A empresa escolhe um anúncio, seleciona o pacote de horas e envia a solicitação com o tema. O criador aceita, o contrato é criado e, depois do pagamento, a gravação é agendada.', 'company'],
            ['Quem define o tema do vídeo?', 'Quem paga. A empresa envia tema, mensagem principal e restrições no briefing. O criador pode sugerir ajustes, mas a palavra final é da empresa.', 'company'],
            ['Por que não vejo o telefone do criador?', 'Para proteger as duas partes, todo o combinado acontece dentro da plataforma: mensagens, arquivos e histórico do contrato.', 'all'],
            ['Quando o criador recebe?', 'Depois que o contrato é concluído, o valor fica em "aguardando repasse" e é transferido pela plataforma, descontada a taxa informada no contrato.', 'creator'],
            ['Meu anúncio foi reprovado. E agora?', 'O motivo aparece no painel, na página do anúncio. Faça os ajustes pedidos e envie de novo para revisão.', 'creator'],
            ['Posso cancelar uma solicitação?', 'Sim, enquanto o criador não responde. Depois do pagamento, o cancelamento é feito pelo suporte.', 'company'],
        ];
        foreach ($faqs as $i => [$q, $a, $audience]) {
            Db::insert('faqs', ['question' => $q, 'answer' => $a, 'audience' => $audience, 'status' => 'visible', 'sort_order' => $i, 'created_at' => $this->ago(100), 'updated_at' => $this->ago(100)]);
        }

        $pages = [
            'como-funciona' => ['Como funciona', "A CinquentaConto conecta empresas a criadores que gravam vídeos com o tema definido por quem paga.\n\nPara empresas: encontre um criador, escolha o pacote de horas, envie o briefing e acompanhe tudo pelo painel: solicitação, contrato, pagamento, entrega e avaliação.\n\nPara criadores: publique anúncios com pacotes de horas e adicionais, responda às solicitações e receba pela plataforma depois da conclusão."],
            'sobre' => ['Sobre', "Nascemos para simplificar a contratação de vídeo para empresas. Em vez de orçamentos longos, pacotes de horas claros, com prazo, revisões e preço definidos no anúncio.\n\nContato, arquivos e combinados ficam dentro da plataforma, com histórico para as duas partes."],
            'termos' => ['Termos de uso', "Ao usar a plataforma, você concorda em manter o contato e os pagamentos dentro dela.\n\nAnúncios passam por moderação e podem ser reprovados quando exibirem dados de contato, conteúdo impróprio ou informação falsa.\n\nA taxa da plataforma é informada no contrato antes do pagamento. Cancelamentos após o pagamento são tratados pelo suporte."],
            'privacidade' => ['Política de privacidade', "Coletamos apenas os dados necessários para operar o marketplace: cadastro, perfil, anúncios, mensagens e registros de contrato.\n\nTelefone e e-mail não são exibidos publicamente. Mensagens só são vistas pelos participantes e, em caso de suporte ou disputa, por administradores autorizados, com registro de acesso.\n\nVocê pode pedir a exclusão da conta pelo e-mail de suporte."],
            'contato' => ['Contato', "Fale com a equipe pelo e-mail suporte@cinquentaconto.com.br.\n\nRespondemos em até dois dias úteis."],
        ];
        foreach ($pages as $slug => [$title, $content]) {
            Db::insert('pages', ['slug' => $slug, 'title' => $title, 'content' => $content, 'status' => 'published', 'created_at' => $this->ago(120), 'updated_at' => $this->ago(120)]);
        }

        $active = array_values(array_filter($listings, static fn (array $l): bool => $l['status'] === 'active'));
        foreach ([0, 2, 3, 4, 7, 9] as $order => $index) {
            Db::insert('home_highlights', ['section' => 'featured_creators', 'item_type' => 'creator', 'item_id' => $creators[$index]['id'], 'sort_order' => $order, 'created_at' => $this->ago(30)]);
        }
        foreach (array_slice($active, 3, 5) as $order => $listing) {
            Db::insert('home_highlights', ['section' => 'video_for_company', 'item_type' => 'listing', 'item_id' => $listing['id'], 'sort_order' => $order, 'created_at' => $this->ago(30)]);
        }
        foreach (array_values($categories) as $order => $categoryId) {
            Db::insert('home_highlights', ['section' => 'video_types', 'item_type' => 'category', 'item_id' => $categoryId, 'sort_order' => $order, 'created_at' => $this->ago(30)]);
        }
    }

    private function extras(array $companies, array $creators, array $listings, int $master, int $staff): void
    {
        $active = array_values(array_filter($listings, static fn (array $l): bool => $l['status'] === 'active'));
        foreach ($companies as $i => $company) {
            foreach ([0, 1] as $k) {
                Db::run(
                    "INSERT INTO favorites (user_id, target_type, target_id, created_at) VALUES (:u, 'listing', :t, :c) ON CONFLICT DO NOTHING",
                    ['u' => $company['id'], 't' => $active[($i + $k * 4) % count($active)]['id'], 'c' => $this->ago(20 - $i)]
                );
            }
            Db::run(
                "INSERT INTO favorites (user_id, target_type, target_id, created_at) VALUES (:u, 'creator', :t, :c) ON CONFLICT DO NOTHING",
                ['u' => $company['id'], 't' => $creators[$i % count($creators)]['id'], 'c' => $this->ago(15)]
            );
        }

        $rejected = array_values(array_filter($listings, static fn (array $l): bool => $l['status'] === 'rejected'))[0];
        $pending = array_values(array_filter($listings, static fn (array $l): bool => $l['status'] === 'pending'))[0];
        $reports = [
            [$companies[2]['id'], 'listing', $rejected['id'], 'contato_externo', 'Aparece um telefone em uma das fotos do anúncio.', 'resolved', 'Anúncio reprovado até a troca das fotos. Obrigado pelo aviso.'],
            [$companies[6]['id'], 'creator', $creators[5]['id'], 'informacao_falsa', 'O perfil diz 10 anos de experiência, mas o portfólio parece recente.', 'reviewing', null],
            [$companies[1]['id'], 'listing', $active[6]['id'], 'outro', 'O prazo do pacote de 8 horas parece curto demais para o que promete.', 'open', null],
        ];
        foreach ($reports as $i => [$reporter, $type, $target, $reason, $description, $status, $response]) {
            $id = Db::insert('reports', [
                'uuid' => uuid4(), 'reporter_id' => $reporter, 'target_type' => $type, 'target_id' => $target, 'reason' => $reason,
                'description' => $description, 'status' => $status, 'response' => $response,
                'resolved_by' => $status === 'resolved' ? $staff : null, 'resolved_at' => $status === 'resolved' ? $this->ago(5) : null,
                'created_at' => $this->ago(12 - $i * 4), 'updated_at' => $this->ago(5),
            ]);
            if ($status !== 'open') {
                Db::insert('report_notes', ['report_id' => $id, 'admin_id' => $staff, 'note' => 'Verificando o conteúdo e o histórico do usuário.', 'created_at' => $this->ago(6)]);
            }
        }

        $notifications = [
            [$creators[0]['id'], 'request_new', 'Nova solicitação recebida', 'Uma empresa quer contratar horas no seu anúncio.', '/painel/solicitacoes'],
            [$creators[0]['id'], 'listing_approved', 'Seu anúncio foi aprovado', null, '/painel/anuncios'],
            [$companies[0]['id'], 'request_accepted', 'Solicitação aceita', 'O contrato foi criado e aguarda pagamento.', '/empresa/contratos'],
            [$pending['creator_id'], 'listing_submitted', 'Anúncio enviado para revisão', $pending['title'], '/painel/anuncios'],
            [$rejected['creator_id'], 'listing_rejected', 'Seu anúncio precisa de ajustes', 'As fotos mostram um número de telefone na parede do estúdio.', '/painel/anuncios/' . $rejected['uuid']],
            [$master, 'listing_pending', 'Novo anúncio aguardando revisão', $pending['title'], '/admin/anuncios/' . $pending['uuid']],
        ];
        foreach ($notifications as $i => [$user, $type, $title, $body, $url]) {
            Db::insert('notifications', ['user_id' => $user, 'type' => $type, 'title' => $title, 'body' => $body, 'url' => $url, 'read_at' => $i % 2 ? $this->ago(1) : null, 'created_at' => $this->ago(3 - intdiv($i, 2))]);
        }

        foreach ([
            [$master, 'listing.approve', 'listing', $active[0]['id'], 'Aprovou o anúncio "' . $active[0]['title'] . '"', 40],
            [$staff, 'listing.reject', 'listing', $rejected['id'], 'Reprovou o anúncio "' . $rejected['title'] . '"', 6],
            [$staff, 'report.update', 'report', null, 'Atualizou denúncia para Resolvida', 5],
            [$master, 'creator.verify', 'user', $creators[0]['id'], 'Verificou ' . $creators[0]['name'], 90],
        ] as [$admin, $action, $type, $object, $description, $days]) {
            Db::insert('admin_actions', ['admin_id' => $admin, 'action' => $action, 'object_type' => $type, 'object_id' => $object, 'description' => $description, 'ip' => '127.0.0.1', 'meta' => null, 'created_at' => $this->ago($days)]);
        }
    }
}
