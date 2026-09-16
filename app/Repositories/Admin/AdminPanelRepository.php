<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use App\Core\Model;

final class AdminPanelRepository extends Model
{
    protected string $table = 'users';

    /** @return array{rows: array<int, array<string, mixed>>, total: int, page: int, pages: int} */
    public function paginate(string $sql, string $countSql, array $params, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $total = (int) ($this->query($countSql, $params)->fetchColumn() ?: 0);
        $rows = $this->query($sql . " LIMIT {$perPage} OFFSET {$offset}", $params)->fetchAll();

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil(max($total, 1) / $perPage)),
        ];
    }

    /** @return array{rows: array<int, array<string, mixed>>, total: int, page: int, pages: int} */
    public function users(string $q, string $status, string $role, int $page): array
    {
        $where = ['u.deleted_at IS NULL'];
        $params = [];
        if ($q !== '') {
            $where[] = '(p.display_name LIKE :q1 OR u.email LIKE :q2 OR p.slug LIKE :q3)';
            $term = '%' . $q . '%';
            $params['q1'] = $term;
            $params['q2'] = $term;
            $params['q3'] = $term;
        }
        if (in_array($status, ['pending', 'active', 'suspended', 'banned'], true)) {
            $where[] = 'u.status = :status';
            $params['status'] = $status;
        }
        if ($role !== '') {
            $where[] = 'EXISTS (SELECT 1 FROM user_roles ur INNER JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = u.id AND r.slug = :role)';
            $params['role'] = $role;
        }
        $sqlWhere = implode(' AND ', $where);

        return $this->paginate(
            "SELECT u.id, u.email, u.status, u.account_type, u.created_at, u.last_login_at, u.email_verified_at,
                    p.display_name, p.slug, p.avatar_path, p.is_verified,
                    (SELECT GROUP_CONCAT(r.slug, ', ') FROM user_roles ur INNER JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = u.id) AS roles
             FROM users u
             LEFT JOIN profiles p ON p.user_id = u.id
             WHERE {$sqlWhere}
             ORDER BY u.created_at DESC",
            "SELECT COUNT(*) FROM users u LEFT JOIN profiles p ON p.user_id = u.id WHERE {$sqlWhere}",
            $params,
            $page
        );
    }

    public function user(int $id): ?array
    {
        $row = $this->query(
            "SELECT u.*, p.display_name, p.professional_name, p.slug, p.headline, p.city, p.state, p.is_verified, p.avatar_path, p.phone
             FROM users u
             LEFT JOIN profiles p ON p.user_id = u.id
             WHERE u.id = :id AND u.deleted_at IS NULL
             LIMIT 1",
            ['id' => $id]
        )->fetch();

        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function allRoles(): array
    {
        return $this->query("SELECT id, name, slug FROM roles WHERE slug != 'visitor' ORDER BY id")->fetchAll();
    }

    /** @return array<int, string> */
    public function userRoleSlugs(int $userId): array
    {
        $rows = $this->query(
            'SELECT r.slug FROM roles r INNER JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = :id',
            ['id' => $userId]
        )->fetchAll();

        return array_map(static fn (array $r): string => (string) $r['slug'], $rows);
    }

    /** @return array{rows: array<int, array<string, mixed>>, total: int, page: int, pages: int} */
    public function services(string $q, string $status, int $page): array
    {
        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(s.title LIKE :q1 OR s.slug LIKE :q2 OR p.display_name LIKE :q3)';
            $term = '%' . $q . '%';
            $params['q1'] = $term;
            $params['q2'] = $term;
            $params['q3'] = $term;
        }
        if ($status !== '') {
            $where[] = 's.status = :status';
            $params['status'] = $status;
        }
        $sqlWhere = implode(' AND ', $where);

        return $this->paginate(
            "SELECT s.id, s.title, s.slug, s.status, s.starting_price_cents, s.is_featured, s.created_at, s.orders_count,
                    c.name AS category_name, c.slug AS category_slug, p.display_name AS seller_name
             FROM services s
             INNER JOIN categories c ON c.id = s.category_id
             INNER JOIN users u ON u.id = s.user_id
             INNER JOIN profiles p ON p.user_id = u.id
             WHERE {$sqlWhere}
             ORDER BY s.created_at DESC",
            "SELECT COUNT(*) FROM services s INNER JOIN categories c ON c.id = s.category_id INNER JOIN users u ON u.id = s.user_id INNER JOIN profiles p ON p.user_id = u.id WHERE {$sqlWhere}",
            $params,
            $page
        );
    }

    public function service(int $id): ?array
    {
        $row = $this->query('SELECT * FROM services WHERE id = :id LIMIT 1', ['id' => $id])->fetch();

        return $row ?: null;
    }

    /** @return array{rows: array<int, array<string, mixed>>, total: int, page: int, pages: int} */
    public function orders(string $q, string $status, int $page): array
    {
        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(o.public_code LIKE :q1 OR buyer.display_name LIKE :q2 OR seller.display_name LIKE :q3)';
            $term = '%' . $q . '%';
            $params['q1'] = $term;
            $params['q2'] = $term;
            $params['q3'] = $term;
        }
        if ($status !== '') {
            $where[] = 'o.status = :status';
            $params['status'] = $status;
        }
        $sqlWhere = implode(' AND ', $where);

        return $this->paginate(
            "SELECT o.id, o.public_code, o.status, o.total_cents, o.seller_amount_cents, o.fee_cents, o.created_at, o.paid_at,
                    buyer.display_name AS buyer_name, seller.display_name AS seller_name, s.title AS service_title
             FROM orders o
             INNER JOIN profiles buyer ON buyer.user_id = o.buyer_id
             INNER JOIN profiles seller ON seller.user_id = o.seller_id
             INNER JOIN services s ON s.id = o.service_id
             WHERE {$sqlWhere}
             ORDER BY o.created_at DESC",
            "SELECT COUNT(*) FROM orders o INNER JOIN profiles buyer ON buyer.user_id = o.buyer_id INNER JOIN profiles seller ON seller.user_id = o.seller_id INNER JOIN services s ON s.id = o.service_id WHERE {$sqlWhere}",
            $params,
            $page
        );
    }

    public function order(int $id): ?array
    {
        $row = $this->query(
            "SELECT o.*, buyer.display_name AS buyer_name, seller.display_name AS seller_name, s.title AS service_title
             FROM orders o
             INNER JOIN profiles buyer ON buyer.user_id = o.buyer_id
             INNER JOIN profiles seller ON seller.user_id = o.seller_id
             INNER JOIN services s ON s.id = o.service_id
             WHERE o.id = :id LIMIT 1",
            ['id' => $id]
        )->fetch();

        return $row ?: null;
    }

    /** @return array{rows: array<int, array<string, mixed>>, total: int, page: int, pages: int} */
    public function projects(string $q, string $status, int $page): array
    {
        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(pr.title LIKE :q1 OR p.display_name LIKE :q2)';
            $term = '%' . $q . '%';
            $params['q1'] = $term;
            $params['q2'] = $term;
        }
        if ($status !== '') {
            $where[] = 'pr.status = :status';
            $params['status'] = $status;
        }
        $sqlWhere = implode(' AND ', $where);

        return $this->paginate(
            "SELECT pr.id, pr.title, pr.slug, pr.status, pr.budget_min_cents, pr.budget_max_cents, pr.proposals_count, pr.created_at, p.display_name AS buyer_name, c.name AS category_name
             FROM projects pr
             INNER JOIN profiles p ON p.user_id = pr.buyer_id
             INNER JOIN categories c ON c.id = pr.category_id
             WHERE {$sqlWhere}
             ORDER BY pr.created_at DESC",
            "SELECT COUNT(*) FROM projects pr INNER JOIN profiles p ON p.user_id = pr.buyer_id INNER JOIN categories c ON c.id = pr.category_id WHERE {$sqlWhere}",
            $params,
            $page
        );
    }

    /** @return array{rows: array<int, array<string, mixed>>, total: int, page: int, pages: int} */
    public function withdrawals(string $status, int $page): array
    {
        $where = ['1=1'];
        $params = [];
        if ($status !== '') {
            $where[] = 'w.status = :status';
            $params['status'] = $status;
        }
        $sqlWhere = implode(' AND ', $where);

        return $this->paginate(
            "SELECT w.*, p.display_name, u.email
             FROM withdrawals w
             INNER JOIN users u ON u.id = w.user_id
             INNER JOIN profiles p ON p.user_id = u.id
             WHERE {$sqlWhere}
             ORDER BY w.created_at DESC",
            "SELECT COUNT(*) FROM withdrawals w INNER JOIN users u ON u.id = w.user_id INNER JOIN profiles p ON p.user_id = u.id WHERE {$sqlWhere}",
            $params,
            $page
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function categories(): array
    {
        return $this->query(
            'SELECT c.*, (SELECT COUNT(*) FROM subcategories s WHERE s.category_id = c.id) AS sub_count,
                    (SELECT COUNT(*) FROM services sv WHERE sv.category_id = c.id) AS service_count
             FROM categories c
             ORDER BY c.sort_order ASC, c.name ASC'
        )->fetchAll();
    }

    public function category(int $id): ?array
    {
        $row = $this->query('SELECT * FROM categories WHERE id = :id LIMIT 1', ['id' => $id])->fetch();

        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function subcategories(int $categoryId): array
    {
        return $this->query(
            'SELECT * FROM subcategories WHERE category_id = :id ORDER BY sort_order ASC, name ASC',
            ['id' => $categoryId]
        )->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function pages(): array
    {
        return $this->query('SELECT id, title, slug, status, sort_order, updated_at FROM pages ORDER BY sort_order ASC, title ASC')->fetchAll();
    }

    public function page(int $id): ?array
    {
        $row = $this->query('SELECT * FROM pages WHERE id = :id LIMIT 1', ['id' => $id])->fetch();

        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function banners(): array
    {
        return $this->query('SELECT * FROM banners ORDER BY placement ASC, sort_order ASC')->fetchAll();
    }

    public function banner(int $id): ?array
    {
        $row = $this->query('SELECT * FROM banners WHERE id = :id LIMIT 1', ['id' => $id])->fetch();

        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function faqs(): array
    {
        return $this->query('SELECT * FROM faqs ORDER BY sort_order ASC')->fetchAll();
    }

    public function faq(int $id): ?array
    {
        $row = $this->query('SELECT * FROM faqs WHERE id = :id LIMIT 1', ['id' => $id])->fetch();

        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function testimonials(): array
    {
        return $this->query('SELECT * FROM testimonials ORDER BY sort_order ASC')->fetchAll();
    }

    public function testimonial(int $id): ?array
    {
        $row = $this->query('SELECT * FROM testimonials WHERE id = :id LIMIT 1', ['id' => $id])->fetch();

        return $row ?: null;
    }

    /** @return array{rows: array<int, array<string, mixed>>, total: int, page: int, pages: int} */
    public function audits(int $page): array
    {
        return $this->paginate(
            "SELECT a.*, p.display_name AS admin_name
             FROM audit_logs a
             LEFT JOIN profiles p ON p.user_id = a.admin_id
             ORDER BY a.created_at DESC",
            'SELECT COUNT(*) FROM audit_logs',
            [],
            $page,
            40
        );
    }

    /** @return array{rows: array<int, array<string, mixed>>, total: int, page: int, pages: int} */
    public function loginLogs(int $page): array
    {
        return $this->paginate(
            'SELECT * FROM login_logs ORDER BY created_at DESC',
            'SELECT COUNT(*) FROM login_logs',
            [],
            $page,
            40
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function recentUsers(int $limit = 6): array
    {
        return $this->query(
            "SELECT u.id, u.email, u.created_at, p.display_name
             FROM users u LEFT JOIN profiles p ON p.user_id = u.id
             WHERE u.deleted_at IS NULL
             ORDER BY u.created_at DESC LIMIT " . (int) $limit
        )->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function recentOrders(int $limit = 6): array
    {
        return $this->query(
            "SELECT o.id, o.public_code, o.status, o.total_cents, o.created_at
             FROM orders o ORDER BY o.created_at DESC LIMIT " . (int) $limit
        )->fetchAll();
    }

    /** @return array<string, int> */
    public function financeSnapshot(): array
    {
        $pdo = $this->db();

        return [
            'gmv' => (int) $pdo->query("SELECT COALESCE(SUM(total_cents),0) FROM orders WHERE status NOT IN ('cancelled','awaiting_payment')")->fetchColumn(),
            'fees' => (int) $pdo->query("SELECT COALESCE(SUM(fee_cents),0) FROM orders WHERE status NOT IN ('cancelled','awaiting_payment')")->fetchColumn(),
            'pending_withdrawals' => (int) $pdo->query("SELECT COALESCE(SUM(amount_cents),0) FROM withdrawals WHERE status IN ('requested','reviewing')")->fetchColumn(),
            'paid_withdrawals' => (int) $pdo->query("SELECT COALESCE(SUM(amount_cents),0) FROM withdrawals WHERE status = 'paid'")->fetchColumn(),
        ];
    }
}
