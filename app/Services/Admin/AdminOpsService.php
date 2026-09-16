<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Cache;
use App\Core\Database;
use App\Models\User;
use App\Repositories\Admin\AdminPanelRepository;
use App\Repositories\UserRepository;

final class AdminOpsService
{
    public function __construct(
        private readonly AdminPanelRepository $panel = new AdminPanelRepository(),
        private readonly UserRepository $users = new UserRepository()
    ) {
    }

    /** @param array<string, mixed> $input */
    public function updateUser(int $id, array $input, User $actor): array
    {
        $user = $this->panel->user($id);
        if (!$user) {
            return ['ok' => false, 'message' => 'Usuário não encontrado.'];
        }

        $status = (string) ($input['status'] ?? $user['status']);
        if (!in_array($status, ['pending', 'active', 'suspended', 'banned'], true)) {
            return ['ok' => false, 'message' => 'Status inválido.'];
        }

        if ($id === $actor->id && in_array($status, ['suspended', 'banned'], true)) {
            return ['ok' => false, 'message' => 'Você não pode bloquear a própria conta.'];
        }

        $this->users->updateById($id, ['status' => $status]);

        $name = trim((string) ($input['display_name'] ?? $user['display_name'] ?? ''));
        if ($name !== '') {
            Database::pdo()->prepare(
                'UPDATE profiles SET display_name = :name, is_verified = :verified, phone = :phone, city = :city, state = :state, updated_at = :updated_at WHERE user_id = :id'
            )->execute([
                'name' => $name,
                'verified' => !empty($input['is_verified']) ? 1 : 0,
                'phone' => trim((string) ($input['phone'] ?? '')),
                'city' => trim((string) ($input['city'] ?? '')),
                'state' => strtoupper(substr(trim((string) ($input['state'] ?? '')), 0, 2)),
                'updated_at' => now(),
                'id' => $id,
            ]);
        }

        $allowed = ['client', 'freelancer', 'moderator', 'admin'];
        if ($actor->hasRole('super_admin')) {
            $allowed[] = 'super_admin';
        }

        $posted = $input['roles'] ?? [];
        if (!is_array($posted)) {
            $posted = [];
        }
        $slugs = array_values(array_intersect($allowed, array_map('strval', $posted)));
        if ($slugs === []) {
            $slugs = ['client'];
        }
        if ($id === $actor->id && $actor->hasRole('super_admin') && !in_array('super_admin', $slugs, true)) {
            $slugs[] = 'super_admin';
        }

        Database::pdo()->prepare('DELETE FROM user_roles WHERE user_id = :id')->execute(['id' => $id]);
        foreach ($slugs as $slug) {
            $roleId = $this->users->roleIdBySlug($slug);
            if ($roleId) {
                $this->users->attachRole($id, $roleId);
            }
        }

        $account = 'buyer';
        if (in_array('freelancer', $slugs, true) && in_array('client', $slugs, true)) {
            $account = 'both';
        } elseif (in_array('freelancer', $slugs, true)) {
            $account = 'seller';
        }
        if (in_array('admin', $slugs, true) || in_array('super_admin', $slugs, true) || in_array('moderator', $slugs, true)) {
            if ($account === 'seller') {
                $account = 'both';
            }
        }
        $this->users->updateById($id, ['account_type' => $account]);

        return ['ok' => true];
    }

    public function setServiceStatus(int $id, string $status): array
    {
        $allowed = ['draft', 'pending_review', 'published', 'paused', 'rejected'];
        if (!in_array($status, $allowed, true)) {
            return ['ok' => false, 'message' => 'Status inválido.'];
        }
        $row = $this->panel->service($id);
        if (!$row) {
            return ['ok' => false, 'message' => 'Serviço não encontrado.'];
        }
        $published = $status === 'published' ? now() : $row['published_at'];
        Database::pdo()->prepare(
            'UPDATE services SET status = :status, published_at = :published_at, updated_at = :updated_at WHERE id = :id'
        )->execute([
            'status' => $status,
            'published_at' => $published,
            'updated_at' => now(),
            'id' => $id,
        ]);

        return ['ok' => true];
    }

    public function toggleServiceFeatured(int $id): array
    {
        $row = $this->panel->service($id);
        if (!$row) {
            return ['ok' => false, 'message' => 'Serviço não encontrado.'];
        }
        $next = empty($row['is_featured']) ? 1 : 0;
        Database::pdo()->prepare('UPDATE services SET is_featured = :f, updated_at = :u WHERE id = :id')
            ->execute(['f' => $next, 'u' => now(), 'id' => $id]);

        return ['ok' => true];
    }

    public function setOrderStatus(int $id, string $status): array
    {
        $allowed = ['awaiting_payment', 'paid', 'in_progress', 'delivered', 'completed', 'cancelled', 'disputed'];
        if (!in_array($status, $allowed, true)) {
            return ['ok' => false, 'message' => 'Status inválido.'];
        }
        if (!$this->panel->order($id)) {
            return ['ok' => false, 'message' => 'Pedido não encontrado.'];
        }
        $extra = '';
        $params = ['status' => $status, 'updated_at' => now(), 'id' => $id];
        if ($status === 'paid') {
            $extra = ', paid_at = :paid_at';
            $params['paid_at'] = now();
        }
        if ($status === 'completed') {
            $extra .= ', completed_at = :completed_at';
            $params['completed_at'] = now();
        }
        if ($status === 'cancelled') {
            $extra .= ', cancelled_at = :cancelled_at';
            $params['cancelled_at'] = now();
        }
        Database::pdo()->prepare("UPDATE orders SET status = :status, updated_at = :updated_at{$extra} WHERE id = :id")
            ->execute($params);

        return ['ok' => true];
    }

    public function setProjectStatus(int $id, string $status): array
    {
        $allowed = ['open', 'in_review', 'hired', 'closed', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            return ['ok' => false, 'message' => 'Status inválido.'];
        }
        Database::pdo()->prepare('UPDATE projects SET status = :status, updated_at = :u WHERE id = :id')
            ->execute(['status' => $status, 'u' => now(), 'id' => $id]);

        return ['ok' => true];
    }

    public function reviewWithdrawal(int $id, string $decision, User $actor): array
    {
        $row = $this->queryOne('SELECT * FROM withdrawals WHERE id = :id', $id);
        if (!$row) {
            return ['ok' => false, 'message' => 'Saque não encontrado.'];
        }
        if ($decision === 'approve') {
            Database::pdo()->prepare(
                'UPDATE withdrawals SET status = :status, reviewer_id = :rid, updated_at = :u WHERE id = :id'
            )->execute(['status' => 'paid', 'rid' => $actor->id, 'u' => now(), 'id' => $id]);
        } elseif ($decision === 'reject') {
            Database::pdo()->prepare(
                'UPDATE withdrawals SET status = :status, reviewer_id = :rid, updated_at = :u WHERE id = :id'
            )->execute(['status' => 'rejected', 'rid' => $actor->id, 'u' => now(), 'id' => $id]);
        } else {
            return ['ok' => false, 'message' => 'Decisão inválida.'];
        }

        return ['ok' => true];
    }

    /** @param array<string, mixed> $input */
    public function saveCategory(?int $id, array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            return ['ok' => false, 'message' => 'Informe o nome da categoria.'];
        }
        $slug = slugify((string) ($input['slug'] ?? $name)) ?: slugify($name);
        $data = [
            'name' => $name,
            'slug' => $slug,
            'short_description' => trim((string) ($input['short_description'] ?? '')),
            'description' => trim((string) ($input['description'] ?? '')),
            'image_path' => trim((string) ($input['image_path'] ?? '')),
            'is_active' => empty($input['is_active']) ? 0 : 1,
            'is_featured' => empty($input['is_featured']) ? 0 : 1,
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'meta_title' => trim((string) ($input['meta_title'] ?? '')),
            'meta_description' => trim((string) ($input['meta_description'] ?? '')),
            'updated_at' => now(),
        ];
        if ($id) {
            Database::pdo()->prepare(
                'UPDATE categories SET name=:name, slug=:slug, short_description=:short_description, description=:description,
                 image_path=:image_path, is_active=:is_active, is_featured=:is_featured, sort_order=:sort_order,
                 meta_title=:meta_title, meta_description=:meta_description, updated_at=:updated_at WHERE id=:id'
            )->execute($data + ['id' => $id]);
        } else {
            $data['created_at'] = now();
            $data['icon'] = null;
            $data['commission_percent'] = null;
            Database::pdo()->prepare(
                'INSERT INTO categories (name, slug, icon, image_path, short_description, description, is_active, is_featured, sort_order, commission_percent, meta_title, meta_description, created_at, updated_at)
                 VALUES (:name, :slug, :icon, :image_path, :short_description, :description, :is_active, :is_featured, :sort_order, :commission_percent, :meta_title, :meta_description, :created_at, :updated_at)'
            )->execute($data);
            $id = (int) Database::pdo()->lastInsertId();
        }
        Cache::forget('categories.menu');

        return ['ok' => true, 'id' => $id];
    }

    /** @param array<string, mixed> $input */
    public function saveSubcategory(int $categoryId, ?int $id, array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            return ['ok' => false, 'message' => 'Informe o nome da subcategoria.'];
        }
        $slug = slugify((string) ($input['slug'] ?? $name)) ?: slugify($name);
        if ($id) {
            Database::pdo()->prepare(
                'UPDATE subcategories SET name=:name, slug=:slug, is_active=:is_active, sort_order=:sort_order, updated_at=:u WHERE id=:id AND category_id=:cid'
            )->execute([
                'name' => $name,
                'slug' => $slug,
                'is_active' => empty($input['is_active']) ? 0 : 1,
                'sort_order' => (int) ($input['sort_order'] ?? 0),
                'u' => now(),
                'id' => $id,
                'cid' => $categoryId,
            ]);
        } else {
            Database::pdo()->prepare(
                'INSERT INTO subcategories (category_id, name, slug, is_active, sort_order, created_at, updated_at)
                 VALUES (:cid, :name, :slug, :is_active, :sort_order, :c, :u)'
            )->execute([
                'cid' => $categoryId,
                'name' => $name,
                'slug' => $slug,
                'is_active' => empty($input['is_active']) ? 0 : 1,
                'sort_order' => (int) ($input['sort_order'] ?? 0),
                'c' => now(),
                'u' => now(),
            ]);
        }
        Cache::forget('categories.menu');

        return ['ok' => true];
    }

    /** @param array<string, mixed> $input */
    public function savePage(?int $id, array $input): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '') {
            return ['ok' => false, 'message' => 'Informe o título.'];
        }
        $slug = slugify((string) ($input['slug'] ?? $title)) ?: slugify($title);
        $status = in_array($input['status'] ?? 'draft', ['published', 'draft'], true) ? (string) $input['status'] : 'draft';
        $payload = [
            'title' => $title,
            'slug' => $slug,
            'content' => (string) ($input['content'] ?? ''),
            'status' => $status,
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'meta_title' => trim((string) ($input['meta_title'] ?? '')),
            'meta_description' => trim((string) ($input['meta_description'] ?? '')),
            'updated_at' => now(),
        ];
        if ($id) {
            Database::pdo()->prepare(
                'UPDATE pages SET title=:title, slug=:slug, content=:content, status=:status, sort_order=:sort_order, meta_title=:meta_title, meta_description=:meta_description, updated_at=:updated_at WHERE id=:id'
            )->execute($payload + ['id' => $id]);
        } else {
            $payload['created_at'] = now();
            Database::pdo()->prepare(
                'INSERT INTO pages (title, slug, content, status, sort_order, meta_title, meta_description, created_at, updated_at)
                 VALUES (:title, :slug, :content, :status, :sort_order, :meta_title, :meta_description, :created_at, :updated_at)'
            )->execute($payload);
            $id = (int) Database::pdo()->lastInsertId();
        }

        return ['ok' => true, 'id' => $id];
    }

    /** @param array<string, mixed> $input */
    public function saveBanner(?int $id, array $input): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '') {
            return ['ok' => false, 'message' => 'Informe o título do banner.'];
        }
        $fields = [
            'placement' => trim((string) ($input['placement'] ?? 'home_hero')) ?: 'home_hero',
            'title' => $title,
            'subtitle' => trim((string) ($input['subtitle'] ?? '')),
            'cta_label' => trim((string) ($input['cta_label'] ?? '')),
            'cta_url' => trim((string) ($input['cta_url'] ?? '')),
            'image_path' => trim((string) ($input['image_path'] ?? '')),
        'starts_at' => ($s = str_replace('T', ' ', trim((string) ($input['starts_at'] ?? '')))) !== '' ? $s : null,
        'ends_at' => ($en = str_replace('T', ' ', trim((string) ($input['ends_at'] ?? '')))) !== '' ? $en : null,
            'is_active' => empty($input['is_active']) ? 0 : 1,
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'updated_at' => now(),
        ];
        if ($id) {
            Database::pdo()->prepare(
                'UPDATE banners SET placement=:placement, title=:title, subtitle=:subtitle, cta_label=:cta_label, cta_url=:cta_url,
                 image_path=:image_path, starts_at=:starts_at, ends_at=:ends_at, is_active=:is_active, sort_order=:sort_order, updated_at=:updated_at WHERE id=:id'
            )->execute($fields + ['id' => $id]);
        } else {
            $fields['created_at'] = now();
            Database::pdo()->prepare(
                'INSERT INTO banners (placement, title, subtitle, cta_label, cta_url, image_path, starts_at, ends_at, is_active, sort_order, created_at, updated_at)
                 VALUES (:placement, :title, :subtitle, :cta_label, :cta_url, :image_path, :starts_at, :ends_at, :is_active, :sort_order, :created_at, :updated_at)'
            )->execute($fields);
            $id = (int) Database::pdo()->lastInsertId();
        }

        return ['ok' => true, 'id' => $id];
    }

    /** @param array<string, mixed> $input */
    public function saveFaq(?int $id, array $input): array
    {
        $q = trim((string) ($input['question'] ?? ''));
        $a = trim((string) ($input['answer'] ?? ''));
        if ($q === '' || $a === '') {
            return ['ok' => false, 'message' => 'Pergunta e resposta são obrigatórias.'];
        }
        $fields = [
            'question' => $q,
            'answer' => $a,
            'placement' => in_array($input['placement'] ?? 'both', ['home', 'help', 'both'], true) ? (string) $input['placement'] : 'both',
            'is_active' => empty($input['is_active']) ? 0 : 1,
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'updated_at' => now(),
        ];
        if ($id) {
            Database::pdo()->prepare(
                'UPDATE faqs SET question=:question, answer=:answer, placement=:placement, is_active=:is_active, sort_order=:sort_order, updated_at=:updated_at WHERE id=:id'
            )->execute($fields + ['id' => $id]);
        } else {
            $fields['created_at'] = now();
            Database::pdo()->prepare(
                'INSERT INTO faqs (question, answer, placement, is_active, sort_order, created_at, updated_at)
                 VALUES (:question, :answer, :placement, :is_active, :sort_order, :created_at, :updated_at)'
            )->execute($fields);
            $id = (int) Database::pdo()->lastInsertId();
        }

        return ['ok' => true, 'id' => $id];
    }

    /** @param array<string, mixed> $input */
    public function saveTestimonial(?int $id, array $input): array
    {
        $name = trim((string) ($input['author_name'] ?? ''));
        $quote = trim((string) ($input['quote'] ?? ''));
        if ($name === '' || $quote === '') {
            return ['ok' => false, 'message' => 'Nome e depoimento são obrigatórios.'];
        }
        $fields = [
            'author_name' => $name,
            'author_role' => trim((string) ($input['author_role'] ?? '')),
            'quote' => $quote,
            'rating' => max(1, min(5, (int) ($input['rating'] ?? 5))),
            'avatar_path' => trim((string) ($input['avatar_path'] ?? '')),
            'is_active' => empty($input['is_active']) ? 0 : 1,
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'updated_at' => now(),
        ];
        if ($id) {
            Database::pdo()->prepare(
                'UPDATE testimonials SET author_name=:author_name, author_role=:author_role, quote=:quote, rating=:rating, avatar_path=:avatar_path, is_active=:is_active, sort_order=:sort_order, updated_at=:updated_at WHERE id=:id'
            )->execute($fields + ['id' => $id]);
        } else {
            $fields['created_at'] = now();
            Database::pdo()->prepare(
                'INSERT INTO testimonials (author_name, author_role, quote, rating, avatar_path, is_active, sort_order, created_at, updated_at)
                 VALUES (:author_name, :author_role, :quote, :rating, :avatar_path, :is_active, :sort_order, :created_at, :updated_at)'
            )->execute($fields);
            $id = (int) Database::pdo()->lastInsertId();
        }

        return ['ok' => true, 'id' => $id];
    }

    public function deleteRow(string $table, int $id): void
    {
        $allowed = ['pages', 'banners', 'faqs', 'testimonials', 'subcategories'];
        if (!in_array($table, $allowed, true)) {
            return;
        }
        Database::pdo()->prepare("DELETE FROM {$table} WHERE id = :id")->execute(['id' => $id]);
        if ($table === 'subcategories') {
            Cache::forget('categories.menu');
        }
    }

    /** @return array<string, mixed>|null */
    private function queryOne(string $sql, int $id): ?array
    {
        $row = Database::pdo()->prepare($sql);
        $row->execute(['id' => $id]);
        $found = $row->fetch();

        return $found ?: null;
    }
}
