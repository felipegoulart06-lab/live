<?php

declare(strict_types=1);

namespace App\Controllers\Creator;

use App\Controllers\Area\AreaController;
use App\Controllers\ListingController as PublicListingController;
use App\Core\Db;
use App\Core\Gate;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Storage;
use App\Queries\ListingQueries;
use App\Services\Listings;
use App\Services\Settings;

final class ListingController extends AreaController
{
    private const LABELS = [
        'category_id' => 'Categoria',
        'title' => 'Título',
        'short_description' => 'Resumo',
        'description' => 'Descrição',
        'what_company_buys' => 'O que a empresa compra',
        'what_company_provides' => 'O que a empresa precisa enviar',
        'how_it_works' => 'Como funciona',
        'framing' => 'Enquadramento e estilo',
        'additional_info' => 'Informações adicionais',
        'video_url' => 'Vídeo de apresentação',
    ];

    public function index(Request $request): Response
    {
        $user = $this->user();
        $status = (string) $request->query('status', '');
        $where = 'WHERE l.creator_id = :u AND l.deleted_at IS NULL';
        $params = ['u' => $user->id];
        if (isset(status_map('listing')[$status])) {
            $where .= ' AND l.status = :s';
            $params['s'] = $status;
        }
        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $where .= ' AND LOWER(l.title) LIKE :q' . Db::ESCAPE;
            $params['q'] = Db::like($q);
        }

        $result = Db::paginate(
            'l.id, l.uuid, l.slug, l.title, l.status, l.cover_path, l.starting_price_cents, l.views_count, l.contacts_count, l.contracts_count,
             l.rating_avg, l.rating_count, l.rejection_reason, l.wizard_step, l.updated_at, c.name AS category_name',
            "FROM listings l LEFT JOIN categories c ON c.id = l.category_id {$where}",
            $params,
            $this->page($request),
            12,
            'l.updated_at DESC, l.id DESC'
        );
        $counts = [];
        foreach (Db::all('SELECT status, COUNT(*) AS total FROM listings WHERE creator_id = :u AND deleted_at IS NULL GROUP BY status', ['u' => $user->id]) as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }

        return $this->render('creator/listings/index', [
            'title' => 'Meus anúncios',
            'result' => $result,
            'status' => $status,
            'q' => $q,
            'counts' => $counts,
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->render('creator/listings/wizard', [
            'title' => 'Novo anúncio',
            'listing' => null,
            'step' => 1,
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $this->user();
        $this->throttle('listing-create:' . $user->id, 20, 3600);
        $data = $this->stepOneData($request);
        if ($response = $this->invalid($data, $this->stepOneRules(), self::LABELS, '/painel/anuncios/novo')) {
            return $response;
        }
        $created = Listings::createDraft($user->id, $data);
        $this->success('Rascunho criado. Agora descreva o que a empresa recebe.');

        return $this->redirect('/painel/anuncios/' . $created['uuid'] . '?etapa=2');
    }

    public function edit(Request $request): Response
    {
        $listing = Gate::ownListing((string) $request->param('id'));
        $step = max(1, min(5, (int) $request->query('etapa', 0) ?: (int) $listing['wizard_step']));
        $details = ListingQueries::details((int) $listing['id'], (int) $listing['creator_id']);

        return $this->render('creator/listings/wizard', $details + [
            'title' => ($listing['title'] ?: 'Anúncio') . ' · editar',
            'listing' => $listing,
            'step' => $step,
            'categories' => $this->categories(),
            'checklist' => Listings::checklist($listing),
            'history' => Db::all(
                'SELECT m.action, m.reason, m.created_at, p.display_name AS actor_name, u.role AS actor_role
                 FROM listing_moderations m LEFT JOIN users u ON u.id = m.actor_id LEFT JOIN profiles p ON p.user_id = m.actor_id
                 WHERE m.listing_id = :id ORDER BY m.created_at DESC, m.id DESC LIMIT 20',
                ['id' => (int) $listing['id']]
            ),
            'limits' => [
                'min_hours' => Settings::int('min_package_hours'),
                'max_hours' => Settings::int('max_package_hours'),
                'max_packages' => Settings::int('max_packages_per_listing'),
                'max_images' => Settings::int('max_images_per_listing'),
            ],
        ]);
    }

    public function saveStep(Request $request): Response
    {
        $listing = Gate::ownListing((string) $request->param('id'));
        $step = (int) $request->param('step');
        $back = '/painel/anuncios/' . $listing['uuid'] . '?etapa=' . $step;
        $id = (int) $listing['id'];

        $response = match ($step) {
            1 => $this->saveService($request, $listing, $back),
            2 => $this->savePresentation($request, $listing, $back),
            3 => $this->savePackages($request, $listing, $back),
            4 => $this->saveAddons($request, $listing, $back),
            default => throw HttpException::notFound(),
        };
        if ($response !== null) {
            return $response;
        }

        Db::update('listings', ['wizard_step' => max((int) $listing['wizard_step'], min(5, $step + 1)), 'updated_at' => now()], ['id' => $id]);
        $newStatus = Listings::afterContentEdit($listing, $step);
        $this->success($newStatus === 'pending' && $listing['status'] !== 'pending'
            ? 'Alterações salvas. Como o conteúdo mudou, o anúncio voltou para revisão.'
            : 'Etapa salva.');

        $next = $request->input('_next') === 'stay' ? $step : min(5, $step + 1);

        return $this->redirect('/painel/anuncios/' . $listing['uuid'] . '?etapa=' . $next);
    }

    public function uploadImages(Request $request): Response
    {
        $user = $this->user();
        $listing = Gate::ownListing((string) $request->param('id'));
        $this->throttle('upload:' . $user->id, 60, 3600);
        $back = '/painel/anuncios/' . $listing['uuid'] . '?etapa=2';

        $files = $this->normalizeFiles($request->files()['images'] ?? []);
        if ($files === []) {
            $this->error('Selecione pelo menos uma imagem.');

            return $this->redirect($back);
        }
        $current = (int) Db::value('SELECT COUNT(*) FROM listing_images WHERE listing_id = :id', ['id' => (int) $listing['id']]);
        $max = Settings::int('max_images_per_listing');
        if ($current + count($files) > $max) {
            $this->error("Cada anúncio aceita até {$max} fotos. Você já tem {$current}.");

            return $this->redirect($back);
        }

        $saved = 0;
        foreach ($files as $i => $file) {
            $stored = Storage::store($file, 'listings/' . $listing['uuid'], true);
            Db::insert('listing_images', [
                'listing_id' => (int) $listing['id'],
                'path' => $stored['path'],
                'thumb_path' => $stored['thumb_path'],
                'alt_text' => mb_substr(trim((string) $request->input('alt_text', '')), 0, 140) ?: $listing['title'],
                'is_primary' => 0,
                'sort_order' => $current + $i,
                'created_at' => now(),
            ]);
            $saved++;
        }
        Listings::syncCover((int) $listing['id']);
        Listings::afterContentEdit($listing, 2);
        $this->success($saved === 1 ? 'Foto enviada.' : "{$saved} fotos enviadas.");

        return $this->redirect($back);
    }

    public function primaryImage(Request $request): Response
    {
        $listing = Gate::ownListing((string) $request->param('id'));
        $image = $this->ownImage($listing, (int) $request->param('image'));
        Db::run('UPDATE listing_images SET is_primary = CASE WHEN id = :id THEN 1 ELSE 0 END WHERE listing_id = :l', ['id' => (int) $image['id'], 'l' => (int) $listing['id']]);
        Listings::syncCover((int) $listing['id']);
        $this->success('Foto de capa atualizada.');

        return $this->redirect('/painel/anuncios/' . $listing['uuid'] . '?etapa=2');
    }

    public function removeImage(Request $request): Response
    {
        $listing = Gate::ownListing((string) $request->param('id'));
        $image = $this->ownImage($listing, (int) $request->param('image'));
        Listings::deleteImage($image);
        $this->success('Foto removida.');

        return $this->redirect('/painel/anuncios/' . $listing['uuid'] . '?etapa=2');
    }

    public function preview(Request $request): Response
    {
        $own = Gate::ownListing((string) $request->param('id'));
        $listing = ListingQueries::anyById((int) $own['id']);

        return $this->view('public/listing', (new PublicListingController())->detailData($listing, true) + ['title' => 'Prévia · ' . ($own['title'] ?: 'Anúncio')]);
    }

    public function submit(Request $request): Response
    {
        $listing = Gate::ownListing((string) $request->param('id'));
        $status = Listings::submit($listing);
        $this->success($status === 'active' ? 'Anúncio publicado.' : 'Anúncio enviado para revisão. Você recebe uma notificação quando a moderação responder.');

        return $this->redirect('/painel/anuncios');
    }

    public function pause(Request $request): Response
    {
        $listing = Gate::ownListing((string) $request->param('id'));
        Listings::setPaused($listing, $this->user()->id, true, false);
        $this->success('Anúncio pausado. Ele não aparece mais nas buscas.');

        return $this->back('/painel/anuncios');
    }

    public function reactivate(Request $request): Response
    {
        $listing = Gate::ownListing((string) $request->param('id'));
        $pausedBy = Db::value("SELECT actor_id FROM listing_moderations WHERE listing_id = :id AND action = 'paused' ORDER BY id DESC LIMIT 1", ['id' => (int) $listing['id']]);
        if ($pausedBy !== null && (int) $pausedBy !== $this->user()->id) {
            throw new HttpException(422, 'Este anúncio foi pausado pela moderação. Fale com o suporte para reativar.');
        }
        Listings::setPaused($listing, $this->user()->id, false, false);
        $this->success('Anúncio reativado.');

        return $this->back('/painel/anuncios');
    }

    public function duplicate(Request $request): Response
    {
        $listing = Gate::ownListing((string) $request->param('id'));
        $this->throttle('listing-create:' . $this->user()->id, 20, 3600);
        $uuid = Listings::duplicate($listing);
        $this->success('Cópia criada como rascunho. Adicione as fotos antes de enviar.');

        return $this->redirect('/painel/anuncios/' . $uuid . '?etapa=1');
    }

    public function destroy(Request $request): Response
    {
        $listing = Gate::ownListing((string) $request->param('id'));
        Listings::softDelete($listing, $this->user()->id, false);
        $this->success('Anúncio excluído.');

        return $this->redirect('/painel/anuncios');
    }

    private function saveService(Request $request, array $listing, string $back): ?Response
    {
        $data = $this->stepOneData($request);
        if ($response = $this->invalid($data, $this->stepOneRules(), self::LABELS, $back)) {
            return $response;
        }
        $update = $data + ['updated_at' => now()];
        if ($listing['status'] === 'draft' && $listing['title'] !== $data['title']) {
            $update['slug'] = Listings::uniqueSlug($data['title'], (int) $listing['id']);
        }
        Db::update('listings', $update, ['id' => (int) $listing['id']]);

        return null;
    }

    private function savePresentation(Request $request, array $listing, string $back): ?Response
    {
        $fields = ['description', 'what_company_buys', 'what_company_provides', 'how_it_works', 'framing', 'additional_info', 'video_url'];
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = trim((string) $request->input($field, ''));
        }
        if ($response = $this->invalid($data, [
            'description' => 'required|min:80|max:5000',
            'what_company_buys' => 'max:1500',
            'what_company_provides' => 'max:1500',
            'how_it_works' => 'max:1500',
            'framing' => 'max:1500',
            'additional_info' => 'max:1500',
            'video_url' => 'url|max:300',
        ], self::LABELS, $back)) {
            return $response;
        }
        if ($data['video_url'] !== '' && Listings::embedUrl($data['video_url']) === null) {
            Session::flash('errors', ['video_url' => ['Use um link do YouTube ou do Vimeo.']]);
            Session::flash('old', $data);

            return $this->redirect($back);
        }

        $questions = (array) $request->input('faq_question', []);
        $answers = (array) $request->input('faq_answer', []);
        $faqs = [];
        foreach ($questions as $i => $question) {
            $question = trim((string) $question);
            $answer = trim((string) ($answers[$i] ?? ''));
            if ($question === '' && $answer === '') {
                continue;
            }
            if ($question === '' || $answer === '' || mb_strlen($question) > 200 || mb_strlen($answer) > 1000) {
                $this->error('Cada pergunta frequente precisa de pergunta (até 200 caracteres) e resposta (até 1000).');
                Session::flash('old', $data);

                return $this->redirect($back);
            }
            $faqs[] = [$question, $answer];
        }
        if (count($faqs) > 8) {
            $this->error('Use no máximo 8 perguntas frequentes.');

            return $this->redirect($back);
        }

        Db::transaction(static function () use ($listing, $data, $faqs): void {
            $data = array_map(static fn (string $v): ?string => $v === '' ? null : $v, $data);
            $data['description'] = (string) $data['description'];
            Db::update('listings', $data + ['updated_at' => now()], ['id' => (int) $listing['id']]);
            Db::run('DELETE FROM listing_faqs WHERE listing_id = :id', ['id' => (int) $listing['id']]);
            foreach ($faqs as $i => [$question, $answer]) {
                Db::insert('listing_faqs', ['listing_id' => (int) $listing['id'], 'question' => $question, 'answer' => $answer, 'sort_order' => $i, 'created_at' => now(), 'updated_at' => now()]);
            }
        });

        return null;
    }

    private function savePackages(Request $request, array $listing, string $back): ?Response
    {
        $rows = $this->rows($request, ['id', 'hours', 'price', 'delivery_days', 'revisions', 'description']);
        $minHours = Settings::int('min_package_hours');
        $maxHours = Settings::int('max_package_hours');
        $maxPackages = Settings::int('max_packages_per_listing');
        $errors = [];
        $clean = [];
        $seenHours = [];
        foreach ($rows as $n => $row) {
            $label = 'Pacote ' . ($n + 1);
            $hours = filter_var($row['hours'], FILTER_VALIDATE_INT);
            $price = parse_money($row['price']);
            $days = filter_var($row['delivery_days'], FILTER_VALIDATE_INT);
            $revisions = $row['revisions'] === '' ? 0 : filter_var($row['revisions'], FILTER_VALIDATE_INT);
            if ($hours === false || $hours < $minHours || $hours > $maxHours) {
                $errors[] = "{$label}: as horas devem ficar entre {$minHours} e {$maxHours}.";
            } elseif (isset($seenHours[$hours])) {
                $errors[] = "{$label}: já existe um pacote de {$hours}h.";
            }
            if ($price === null || $price < 1000) {
                $errors[] = "{$label}: informe um preço de pelo menos R$ 10,00.";
            }
            if ($days === false || $days < 1 || $days > 90) {
                $errors[] = "{$label}: o prazo de entrega deve ficar entre 1 e 90 dias.";
            }
            if ($revisions === false || $revisions < 0 || $revisions > 10) {
                $errors[] = "{$label}: as revisões devem ficar entre 0 e 10.";
            }
            if (mb_strlen($row['description']) > 500) {
                $errors[] = "{$label}: a descrição aceita até 500 caracteres.";
            }
            $seenHours[(int) $hours] = true;
            $clean[] = ['id' => (int) $row['id'], 'hours' => (int) $hours, 'price_cents' => (int) $price, 'delivery_days' => (int) $days, 'revisions' => (int) $revisions, 'description' => $row['description'] ?: null];
        }
        if ($clean === []) {
            $errors[] = 'Cadastre pelo menos um pacote de horas.';
        }
        if (count($clean) > $maxPackages) {
            $errors[] = "Use no máximo {$maxPackages} pacotes.";
        }
        if ($errors !== []) {
            Session::flash('error', implode(' ', $errors));
            Session::flash('old', ['rows' => $rows]);

            return $this->redirect($back);
        }

        $this->syncRows('listing_packages', (int) $listing['id'], $clean);
        Listings::refreshPricing((int) $listing['id']);

        return null;
    }

    private function saveAddons(Request $request, array $listing, string $back): ?Response
    {
        $rows = $this->rows($request, ['id', 'name', 'description', 'price', 'extra_days']);
        $errors = [];
        $clean = [];
        foreach ($rows as $n => $row) {
            $label = 'Adicional ' . ($n + 1);
            $price = parse_money($row['price']);
            $days = $row['extra_days'] === '' ? 0 : filter_var($row['extra_days'], FILTER_VALIDATE_INT);
            if (mb_strlen($row['name']) < 3 || mb_strlen($row['name']) > 80) {
                $errors[] = "{$label}: o nome deve ter entre 3 e 80 caracteres.";
            }
            if ($price === null) {
                $errors[] = "{$label}: informe o preço.";
            }
            if ($days === false || $days < 0 || $days > 30) {
                $errors[] = "{$label}: os dias extras devem ficar entre 0 e 30.";
            }
            if (mb_strlen($row['description']) > 300) {
                $errors[] = "{$label}: a descrição aceita até 300 caracteres.";
            }
            $clean[] = ['id' => (int) $row['id'], 'name' => $row['name'], 'description' => $row['description'] ?: null, 'price_cents' => (int) $price, 'extra_days' => (int) $days];
        }
        if (count($clean) > 10) {
            $errors[] = 'Use no máximo 10 adicionais.';
        }
        if ($errors !== []) {
            Session::flash('error', implode(' ', $errors));
            Session::flash('old', ['rows' => $rows]);

            return $this->redirect($back);
        }
        $this->syncRows('listing_addons', (int) $listing['id'], $clean);

        return null;
    }

    /** Updates rows that keep their id, inserts new ones and deletes the ones removed from the form. */
    private function syncRows(string $table, int $listingId, array $rows): void
    {
        Db::transaction(static function () use ($table, $listingId, $rows): void {
            $existing = array_map('intval', array_column(Db::all("SELECT id FROM {$table} WHERE listing_id = :l", ['l' => $listingId]), 'id'));
            $kept = [];
            foreach ($rows as $i => $row) {
                $id = $row['id'];
                unset($row['id']);
                $row['sort_order'] = $i;
                $row['updated_at'] = now();
                if ($id > 0 && in_array($id, $existing, true)) {
                    Db::update($table, $row, ['id' => $id, 'listing_id' => $listingId]);
                    $kept[] = $id;
                } else {
                    Db::insert($table, $row + ['listing_id' => $listingId, 'created_at' => now()]);
                }
            }
            foreach (array_diff($existing, $kept) as $removed) {
                Db::run("DELETE FROM {$table} WHERE id = :id AND listing_id = :l", ['id' => $removed, 'l' => $listingId]);
            }
        });
    }

    /** @param array<int, string> $keys @return array<int, array<string, string>> non-empty rows of a repeated form group */
    private function rows(Request $request, array $keys): array
    {
        $columns = [];
        foreach ($keys as $key) {
            $columns[$key] = array_values((array) $request->input($key, []));
        }
        $rows = [];
        $count = max(array_map('count', $columns));
        for ($i = 0; $i < min($count, 20); $i++) {
            $row = [];
            foreach ($keys as $key) {
                $row[$key] = trim((string) ($columns[$key][$i] ?? ''));
            }
            $filled = array_filter(array_diff_key($row, ['id' => 1]), static fn (string $v): bool => $v !== '');
            if ($filled !== []) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /** @return array<string, string> */
    private function stepOneData(Request $request): array
    {
        return [
            'category_id' => (string) $request->input('category_id', ''),
            'title' => trim((string) $request->input('title', '')),
            'short_description' => trim((string) $request->input('short_description', '')),
        ];
    }

    /** @return array<string, string> */
    private function stepOneRules(): array
    {
        $ids = implode(',', array_column($this->categories(), 'id'));

        return [
            'category_id' => 'required|in:' . $ids,
            'title' => 'required|min:10|max:90',
            'short_description' => 'required|min:20|max:200',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function categories(): array
    {
        return Db::all("SELECT id, name FROM categories WHERE status = 'visible' ORDER BY sort_order, name");
    }

    /** @return array<string, mixed> */
    private function ownImage(array $listing, int $imageId): array
    {
        $image = Db::first('SELECT * FROM listing_images WHERE id = :id AND listing_id = :l', ['id' => $imageId, 'l' => (int) $listing['id']]);

        return $image ?? throw HttpException::notFound('Foto não encontrada.');
    }

    /** @return array<int, array<string, mixed>> */
    private function normalizeFiles(mixed $input): array
    {
        if (!is_array($input) || !isset($input['name'])) {
            return [];
        }
        if (!is_array($input['name'])) {
            return ($input['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE ? [] : [$input];
        }
        $files = [];
        foreach ($input['name'] as $i => $name) {
            if (($input['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $files[] = ['name' => $name, 'type' => $input['type'][$i] ?? '', 'tmp_name' => $input['tmp_name'][$i] ?? '', 'error' => $input['error'][$i] ?? 0, 'size' => $input['size'][$i] ?? 0];
        }

        return array_slice($files, 0, 10);
    }
}
