<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Db;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Storage;
use App\Services\Audit;
use App\Services\Settings;

final class ContentController extends AdminController
{
    public const SECTIONS = [
        'featured_creators' => ['Criadores em destaque', 'creator'],
        'most_hired' => ['Horas mais contratadas', 'listing'],
        'new_listings' => ['Novos anúncios', 'listing'],
        'video_for_company' => ['Vídeo feito para a sua empresa', 'listing'],
        'video_types' => ['Tipos de vídeo', 'category'],
    ];

    public function categories(Request $request): Response
    {
        $edit = null;
        if ($request->query('editar')) {
            $edit = Db::first('SELECT * FROM categories WHERE id = :id', ['id' => (int) $request->query('editar')]);
        }

        return $this->render('admin/content/categories', [
            'title' => 'Categorias',
            'edit' => $edit,
            'categories' => Db::all(
                'SELECT c.*, (SELECT COUNT(*) FROM listings l WHERE l.category_id = c.id AND l.deleted_at IS NULL) AS listings_count
                 FROM categories c ORDER BY c.sort_order, c.name'
            ),
        ]);
    }

    public function saveCategory(Request $request): Response
    {
        $id = (int) $request->input('id', 0);
        $data = [
            'name' => trim((string) $request->input('name', '')),
            'description' => trim((string) $request->input('description', '')),
            'status' => (string) $request->input('status', 'visible'),
            'sort_order' => (string) $request->input('sort_order', '0'),
        ];
        if ($response = $this->invalid($data, [
            'name' => 'required|min:2|max:60',
            'description' => 'max:300',
            'status' => 'required|in:visible,hidden',
            'sort_order' => 'required|between:0,999',
        ], ['name' => 'Nome', 'description' => 'Descrição', 'status' => 'Situação', 'sort_order' => 'Ordem'], '/admin/categorias')) {
            return $response;
        }
        $slug = slugify($data['name']);
        if (Db::value('SELECT 1 FROM categories WHERE slug = :s AND id <> :id', ['s' => $slug, 'id' => $id])) {
            throw new HttpException(422, 'Já existe uma categoria com este nome.');
        }
        $row = ['name' => $data['name'], 'slug' => $slug, 'description' => $data['description'] ?: null, 'status' => $data['status'], 'sort_order' => (int) $data['sort_order'], 'updated_at' => now()];

        $file = $request->files()['image'] ?? null;
        if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $stored = Storage::store($file, 'listings/categorias', true);
            $row['image_path'] = $stored['thumb_path'] ?? $stored['path'];
        }

        if ($id > 0) {
            $current = Db::first('SELECT slug FROM categories WHERE id = :id', ['id' => $id]) ?? throw HttpException::notFound();
            Db::update('categories', $row, ['id' => $id]);
            Audit::admin('category.update', 'category', $id, 'Editou a categoria ' . $data['name'], $current['slug'] !== $slug ? ['old_slug' => $current['slug']] : []);
        } else {
            $id = Db::insert('categories', $row + ['created_at' => now()]);
            Audit::admin('category.create', 'category', $id, 'Criou a categoria ' . $data['name']);
        }
        $this->success('Categoria salva.');

        return $this->redirect('/admin/categorias');
    }

    public function deleteCategory(Request $request): Response
    {
        $category = Db::first('SELECT * FROM categories WHERE id = :id', ['id' => (int) $request->param('id')]) ?? throw HttpException::notFound();
        if ((int) Db::value('SELECT COUNT(*) FROM listings WHERE category_id = :id AND deleted_at IS NULL', ['id' => (int) $category['id']]) > 0) {
            throw new HttpException(422, 'Esta categoria tem anúncios. Mova os anúncios ou oculte a categoria.');
        }
        Db::run('DELETE FROM categories WHERE id = :id', ['id' => (int) $category['id']]);
        Db::run("DELETE FROM home_highlights WHERE item_type = 'category' AND item_id = :id", ['id' => (int) $category['id']]);
        Audit::admin('category.delete', 'category', (int) $category['id'], 'Excluiu a categoria ' . $category['name']);
        $this->success('Categoria excluída.');

        return $this->redirect('/admin/categorias');
    }

    public function highlights(Request $request): Response
    {
        $section = array_key_exists((string) $request->query('secao'), self::SECTIONS) ? (string) $request->query('secao') : 'featured_creators';
        $type = self::SECTIONS[$section][1];
        $items = Db::all(
            'SELECT h.id, h.sort_order, h.item_id, ' . match ($type) {
                'creator' => 'p.display_name AS label, p.slug AS slug FROM home_highlights h JOIN profiles p ON p.user_id = h.item_id',
                'listing' => 'l.title AS label, l.slug AS slug FROM home_highlights h JOIN listings l ON l.id = h.item_id',
                default => 'c.name AS label, c.slug AS slug FROM home_highlights h JOIN categories c ON c.id = h.item_id',
            } . ' WHERE h.section = :s ORDER BY h.sort_order, h.id',
            ['s' => $section]
        );

        $q = trim((string) $request->query('q', ''));
        $candidates = [];
        if ($q !== '' || $type === 'category') {
            $like = Db::like($q);
            $candidates = match ($type) {
                'creator' => Db::all("SELECT u.id, p.display_name AS label FROM users u JOIN profiles p ON p.user_id = u.id WHERE u.role = 'creator' AND u.status = 'active' AND u.deleted_at IS NULL AND LOWER(p.display_name) LIKE :q" . Db::ESCAPE . ' ORDER BY p.display_name LIMIT 20', ['q' => $like]),
                'listing' => Db::all("SELECT l.id, l.title AS label FROM listings l WHERE l.status = 'active' AND l.deleted_at IS NULL AND LOWER(l.title) LIKE :q" . Db::ESCAPE . ' ORDER BY l.title LIMIT 20', ['q' => $like]),
                default => Db::all("SELECT id, name AS label FROM categories WHERE status = 'visible' ORDER BY sort_order, name"),
            };
        }

        return $this->render('admin/content/highlights', [
            'title' => 'Destaques da home',
            'section' => $section,
            'type' => $type,
            'items' => $items,
            'candidates' => $candidates,
            'q' => $q,
        ]);
    }

    public function addHighlight(Request $request): Response
    {
        $section = (string) $request->input('section', '');
        if (!array_key_exists($section, self::SECTIONS)) {
            throw HttpException::notFound();
        }
        $type = self::SECTIONS[$section][1];
        $itemId = (int) $request->input('item_id', 0);
        $exists = match ($type) {
            'creator' => Db::value("SELECT 1 FROM users WHERE id = :id AND role = 'creator'", ['id' => $itemId]),
            'listing' => Db::value('SELECT 1 FROM listings WHERE id = :id AND deleted_at IS NULL', ['id' => $itemId]),
            default => Db::value('SELECT 1 FROM categories WHERE id = :id', ['id' => $itemId]),
        };
        if (!$exists) {
            throw HttpException::notFound();
        }
        if (Db::value('SELECT 1 FROM home_highlights WHERE section = :s AND item_type = :t AND item_id = :id', ['s' => $section, 't' => $type, 'id' => $itemId])) {
            throw new HttpException(422, 'Este item já está na seção.');
        }
        $order = (int) Db::value('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM home_highlights WHERE section = :s', ['s' => $section]);
        Db::insert('home_highlights', ['section' => $section, 'item_type' => $type, 'item_id' => $itemId, 'sort_order' => $order, 'created_at' => now()]);
        Audit::admin('highlight.add', 'home_highlight', $itemId, 'Adicionou item em "' . self::SECTIONS[$section][0] . '"');
        $this->success('Adicionado aos destaques.');

        return $this->redirect('/admin/destaques?secao=' . $section);
    }

    public function moveHighlight(Request $request): Response
    {
        $item = Db::first('SELECT * FROM home_highlights WHERE id = :id', ['id' => (int) $request->param('id')]) ?? throw HttpException::notFound();
        $direction = $request->input('direction') === 'up' ? -1 : 1;
        $rows = Db::all('SELECT id FROM home_highlights WHERE section = :s ORDER BY sort_order, id', ['s' => $item['section']]);
        $ids = array_map('intval', array_column($rows, 'id'));
        $pos = array_search((int) $item['id'], $ids, true);
        $swap = $pos + $direction;
        if ($pos !== false && isset($ids[$swap])) {
            [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
            Db::transaction(static function () use ($ids): void {
                foreach ($ids as $i => $id) {
                    Db::run('UPDATE home_highlights SET sort_order = :o WHERE id = :id', ['o' => $i, 'id' => $id]);
                }
            });
        }

        return $this->redirect('/admin/destaques?secao=' . $item['section']);
    }

    public function removeHighlight(Request $request): Response
    {
        $item = Db::first('SELECT * FROM home_highlights WHERE id = :id', ['id' => (int) $request->param('id')]) ?? throw HttpException::notFound();
        Db::run('DELETE FROM home_highlights WHERE id = :id', ['id' => (int) $item['id']]);
        Audit::admin('highlight.remove', 'home_highlight', (int) $item['item_id'], 'Removeu item de "' . (self::SECTIONS[$item['section']][0] ?? $item['section']) . '"');
        $this->success('Removido dos destaques.');

        return $this->redirect('/admin/destaques?secao=' . $item['section']);
    }

    public function home(Request $request): Response
    {
        return $this->render('admin/content/home', [
            'title' => 'Conteúdo da home',
            'values' => [
                'home_hero_title' => Settings::get('home_hero_title'),
                'home_hero_subtitle' => Settings::get('home_hero_subtitle'),
                'platform_tagline' => Settings::get('platform_tagline'),
            ],
        ]);
    }

    public function saveHome(Request $request): Response
    {
        $data = [
            'home_hero_title' => trim((string) $request->input('home_hero_title', '')),
            'home_hero_subtitle' => trim((string) $request->input('home_hero_subtitle', '')),
            'platform_tagline' => trim((string) $request->input('platform_tagline', '')),
        ];
        if ($response = $this->invalid($data, [
            'home_hero_title' => 'required|min:10|max:90',
            'home_hero_subtitle' => 'required|min:20|max:300',
            'platform_tagline' => 'required|min:10|max:160',
        ], ['home_hero_title' => 'Título principal', 'home_hero_subtitle' => 'Subtítulo', 'platform_tagline' => 'Frase do rodapé'], '/admin/conteudo')) {
            return $response;
        }
        $file = $request->files()['home_hero_image'] ?? null;
        if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $stored = Storage::store($file, 'listings/home', true);
            $data['home_hero_image'] = $stored['path'];
        }
        Settings::putMany($data);
        Audit::admin('content.home', 'settings', null, 'Atualizou o conteúdo da home');
        $this->success('Conteúdo da home atualizado.');

        return $this->redirect('/admin/conteudo');
    }

    public function faqs(Request $request): Response
    {
        $edit = $request->query('editar') ? Db::first('SELECT * FROM faqs WHERE id = :id', ['id' => (int) $request->query('editar')]) : null;

        return $this->render('admin/content/faqs', [
            'title' => 'FAQ',
            'edit' => $edit,
            'faqs' => Db::all('SELECT * FROM faqs ORDER BY sort_order, id'),
        ]);
    }

    public function saveFaq(Request $request): Response
    {
        $id = (int) $request->input('id', 0);
        $data = [
            'question' => trim((string) $request->input('question', '')),
            'answer' => trim((string) $request->input('answer', '')),
            'audience' => (string) $request->input('audience', 'all'),
            'status' => (string) $request->input('status', 'visible'),
            'sort_order' => (string) $request->input('sort_order', '0'),
        ];
        if ($response = $this->invalid($data, [
            'question' => 'required|min:5|max:200',
            'answer' => 'required|min:10|max:2000',
            'audience' => 'required|in:all,company,creator',
            'status' => 'required|in:visible,hidden',
            'sort_order' => 'required|between:0,999',
        ], ['question' => 'Pergunta', 'answer' => 'Resposta', 'audience' => 'Público', 'status' => 'Situação', 'sort_order' => 'Ordem'], '/admin/faq')) {
            return $response;
        }
        $data['sort_order'] = (int) $data['sort_order'];
        if ($id > 0) {
            Db::update('faqs', $data + ['updated_at' => now()], ['id' => $id]);
        } else {
            $id = Db::insert('faqs', $data + ['created_at' => now(), 'updated_at' => now()]);
        }
        Audit::admin('faq.save', 'faq', $id, 'Salvou a pergunta "' . mb_substr($data['question'], 0, 80) . '"');
        $this->success('Pergunta salva.');

        return $this->redirect('/admin/faq');
    }

    public function deleteFaq(Request $request): Response
    {
        $faq = Db::first('SELECT * FROM faqs WHERE id = :id', ['id' => (int) $request->param('id')]) ?? throw HttpException::notFound();
        Db::run('DELETE FROM faqs WHERE id = :id', ['id' => (int) $faq['id']]);
        Audit::admin('faq.delete', 'faq', (int) $faq['id'], 'Excluiu a pergunta "' . mb_substr((string) $faq['question'], 0, 80) . '"');
        $this->success('Pergunta excluída.');

        return $this->redirect('/admin/faq');
    }

    public function pages(Request $request): Response
    {
        $slug = (string) $request->query('pagina', '');
        $edit = in_array($slug, \App\Controllers\PageController::SLUGS, true)
            ? (Db::first('SELECT * FROM pages WHERE slug = :s', ['s' => $slug]) ?? ['slug' => $slug, 'title' => '', 'content' => '', 'status' => 'draft'])
            : null;

        return $this->render('admin/content/pages', [
            'title' => 'Páginas',
            'edit' => $edit,
            'pages' => Db::all('SELECT slug, title, status, updated_at FROM pages ORDER BY title'),
            'slugs' => \App\Controllers\PageController::SLUGS,
        ]);
    }

    public function savePage(Request $request): Response
    {
        $slug = (string) $request->input('slug', '');
        if (!in_array($slug, \App\Controllers\PageController::SLUGS, true)) {
            throw HttpException::notFound();
        }
        $data = [
            'title' => trim((string) $request->input('title', '')),
            'content' => trim((string) $request->input('content', '')),
            'status' => (string) $request->input('status', 'published'),
        ];
        if ($response = $this->invalid($data, [
            'title' => 'required|min:3|max:120',
            'content' => 'required|min:20|max:30000',
            'status' => 'required|in:published,draft',
        ], ['title' => 'Título', 'content' => 'Conteúdo', 'status' => 'Situação'], '/admin/paginas?pagina=' . $slug)) {
            return $response;
        }
        Db::run(
            'INSERT INTO pages (slug, title, content, status, created_at, updated_at) VALUES (:s, :t, :c, :st, :now, :now2)
             ON CONFLICT (slug) DO UPDATE SET title = excluded.title, content = excluded.content, status = excluded.status, updated_at = excluded.updated_at',
            ['s' => $slug, 't' => $data['title'], 'c' => $data['content'], 'st' => $data['status'], 'now' => now(), 'now2' => now()]
        );
        Audit::admin('page.save', 'page', null, 'Atualizou a página "' . $data['title'] . '"');
        $this->success('Página salva.');

        return $this->redirect('/admin/paginas?pagina=' . $slug);
    }
}
