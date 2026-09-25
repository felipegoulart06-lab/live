<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use App\Core\HttpException;
use App\Core\Storage;

final class Listings
{
    public const STEPS = [
        1 => 'Serviço',
        2 => 'Apresentação',
        3 => 'Horas e preços',
        4 => 'Adicionais',
        5 => 'Publicação',
    ];

    public static function createDraft(int $creatorId, array $data): array
    {
        $now = now();
        $uuid = uuid4();
        $id = Db::insert('listings', [
            'uuid' => $uuid,
            'creator_id' => $creatorId,
            'category_id' => (int) $data['category_id'],
            'title' => trim((string) $data['title']),
            'slug' => self::uniqueSlug((string) $data['title']),
            'short_description' => trim((string) $data['short_description']),
            'status' => 'draft',
            'wizard_step' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        Audit::activity($creatorId, 'listing_created', 'Criou o rascunho "' . trim((string) $data['title']) . '"', 'listing', $id);

        return ['id' => $id, 'uuid' => $uuid];
    }

    public static function uniqueSlug(string $title, ?int $exceptId = null): string
    {
        $base = slugify($title) ?: 'anuncio';
        $slug = $base;
        $i = 2;
        while (Db::value('SELECT 1 FROM listings WHERE slug = :s' . ($exceptId ? ' AND id <> :id' : ''), array_filter(['s' => $slug, 'id' => $exceptId]))) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    /**
     * Content edits on a live listing go back to review; prices and add-ons do not.
     * @param array<string, mixed> $listing
     */
    public static function afterContentEdit(array $listing, int $step): string
    {
        $status = (string) $listing['status'];
        $needsReview = in_array($step, [1, 2], true)
            && in_array($status, ['active', 'paused'], true)
            && Settings::get('require_listing_approval', '1') === '1';

        if ($needsReview) {
            Db::update('listings', ['status' => 'pending', 'submitted_at' => now(), 'updated_at' => now()], ['id' => (int) $listing['id']]);
            self::logModeration((int) $listing['id'], (int) $listing['creator_id'], 'submitted', 'Reenviado após edição de conteúdo');
            Notifier::admins('listing_pending', 'Anúncio editado aguarda revisão', (string) $listing['title'], '/admin/anuncios/' . $listing['uuid']);

            return 'pending';
        }

        return $status;
    }

    public static function refreshPricing(int $listingId): void
    {
        $row = Db::first('SELECT MIN(price_cents) AS price, MIN(delivery_days) AS days FROM listing_packages WHERE listing_id = :id', ['id' => $listingId]);
        Db::update('listings', [
            'starting_price_cents' => (int) ($row['price'] ?? 0),
            'min_delivery_days' => (int) ($row['days'] ?? 0),
            'updated_at' => now(),
        ], ['id' => $listingId]);
    }

    /** @return array<string, array{ok: bool, label: string, step: int}> */
    public static function checklist(array $listing): array
    {
        $id = (int) $listing['id'];
        $images = (int) Db::value('SELECT COUNT(*) FROM listing_images WHERE listing_id = :id', ['id' => $id]);
        $packages = (int) Db::value('SELECT COUNT(*) FROM listing_packages WHERE listing_id = :id', ['id' => $id]);

        return [
            'category' => ['ok' => !empty($listing['category_id']), 'label' => 'Categoria escolhida', 'step' => 1],
            'title' => ['ok' => mb_strlen((string) $listing['title']) >= 10, 'label' => 'Título com pelo menos 10 caracteres', 'step' => 1],
            'short' => ['ok' => mb_strlen((string) $listing['short_description']) >= 20, 'label' => 'Resumo com pelo menos 20 caracteres', 'step' => 1],
            'description' => ['ok' => mb_strlen((string) $listing['description']) >= 80, 'label' => 'Descrição com pelo menos 80 caracteres', 'step' => 2],
            'images' => ['ok' => $images > 0, 'label' => 'Pelo menos uma foto', 'step' => 2],
            'packages' => ['ok' => $packages > 0, 'label' => 'Pelo menos um pacote de horas', 'step' => 3],
        ];
    }

    public static function submit(array $listing): string
    {
        if (!in_array($listing['status'], ['draft', 'rejected', 'paused'], true)) {
            throw new HttpException(422, 'Este anúncio não pode ser enviado agora.');
        }
        foreach (self::checklist($listing) as $item) {
            if (!$item['ok']) {
                throw new HttpException(422, 'Complete os itens pendentes antes de enviar: ' . $item['label'] . '.');
            }
        }

        $now = now();
        $autoApprove = Settings::get('require_listing_approval', '1') !== '1';
        Db::update('listings', [
            'status' => $autoApprove ? 'active' : 'pending',
            'rejection_reason' => null,
            'submitted_at' => $now,
            'approved_at' => $autoApprove ? $now : null,
            'published_at' => $autoApprove ? ($listing['published_at'] ?? $now) : $listing['published_at'],
            'updated_at' => $now,
        ], ['id' => (int) $listing['id']]);
        self::logModeration((int) $listing['id'], (int) $listing['creator_id'], 'submitted', null);
        Audit::activity((int) $listing['creator_id'], 'listing_submitted', 'Enviou "' . $listing['title'] . '" para revisão', 'listing', (int) $listing['id']);
        if (!$autoApprove) {
            Notifier::admins('listing_pending', 'Novo anúncio aguardando revisão', (string) $listing['title'], '/admin/anuncios/' . $listing['uuid']);
        }

        return $autoApprove ? 'active' : 'pending';
    }

    public static function approve(array $listing, int $adminId): void
    {
        $now = now();
        Db::update('listings', [
            'status' => 'active',
            'rejection_reason' => null,
            'approved_at' => $now,
            'published_at' => $listing['published_at'] ?? $now,
            'updated_at' => $now,
        ], ['id' => (int) $listing['id']]);
        self::logModeration((int) $listing['id'], $adminId, 'approved', null);
        Audit::admin('listing.approve', 'listing', (int) $listing['id'], 'Aprovou o anúncio "' . $listing['title'] . '"');
        Notifier::send((int) $listing['creator_id'], 'listing_approved', 'Seu anúncio foi aprovado', '"' . $listing['title'] . '" já aparece no site.', '/painel/anuncios/' . $listing['uuid']);
    }

    public static function reject(array $listing, int $adminId, string $reason): void
    {
        Db::update('listings', ['status' => 'rejected', 'rejection_reason' => $reason, 'updated_at' => now()], ['id' => (int) $listing['id']]);
        self::logModeration((int) $listing['id'], $adminId, 'rejected', $reason);
        Audit::admin('listing.reject', 'listing', (int) $listing['id'], 'Reprovou o anúncio "' . $listing['title'] . '"', ['reason' => $reason]);
        Notifier::send((int) $listing['creator_id'], 'listing_rejected', 'Seu anúncio precisa de ajustes', $reason, '/painel/anuncios/' . $listing['uuid']);
    }

    public static function setPaused(array $listing, int $actorId, bool $paused, bool $byAdmin, ?string $reason = null): void
    {
        $from = $paused ? ['active'] : ['paused'];
        if (!in_array($listing['status'], $from, true)) {
            throw new HttpException(422, $paused ? 'Só anúncios ativos podem ser pausados.' : 'Só anúncios pausados podem ser reativados.');
        }
        Db::update('listings', ['status' => $paused ? 'paused' : 'active', 'updated_at' => now()], ['id' => (int) $listing['id']]);
        self::logModeration((int) $listing['id'], $actorId, $paused ? 'paused' : 'reactivated', $reason);
        if ($byAdmin) {
            Audit::admin($paused ? 'listing.pause' : 'listing.reactivate', 'listing', (int) $listing['id'], ($paused ? 'Pausou' : 'Reativou') . ' o anúncio "' . $listing['title'] . '"', $reason ? ['reason' => $reason] : []);
            Notifier::send((int) $listing['creator_id'], 'listing_status', $paused ? 'Seu anúncio foi pausado pela moderação' : 'Seu anúncio foi reativado', $reason ?? (string) $listing['title'], '/painel/anuncios/' . $listing['uuid']);
        } else {
            Audit::activity($actorId, $paused ? 'listing_paused' : 'listing_reactivated', ($paused ? 'Pausou' : 'Reativou') . ' "' . $listing['title'] . '"', 'listing', (int) $listing['id']);
        }
    }

    public static function softDelete(array $listing, int $actorId, bool $byAdmin): void
    {
        $open = (int) Db::value(
            "SELECT COUNT(*) FROM contracts WHERE listing_id = :id AND status IN ('awaiting_payment', 'confirmed', 'in_progress')",
            ['id' => (int) $listing['id']]
        );
        if ($open > 0) {
            throw new HttpException(422, 'Este anúncio tem contratos em aberto. Pause o anúncio e conclua os contratos antes de excluir.');
        }
        Db::transaction(static function () use ($listing, $actorId): void {
            Db::update('listings', ['deleted_at' => now(), 'status' => 'paused', 'updated_at' => now()], ['id' => (int) $listing['id']]);
            Db::run("UPDATE requests SET status = 'cancelled', updated_at = :now WHERE listing_id = :id AND status = 'pending'", ['now' => now(), 'id' => (int) $listing['id']]);
            Db::run("DELETE FROM home_highlights WHERE item_type = 'listing' AND item_id = :id", ['id' => (int) $listing['id']]);
            self::logModeration((int) $listing['id'], $actorId, 'deleted', null);
        });
        if ($byAdmin) {
            Audit::admin('listing.delete', 'listing', (int) $listing['id'], 'Excluiu o anúncio "' . $listing['title'] . '"');
        } else {
            Audit::activity($actorId, 'listing_deleted', 'Excluiu "' . $listing['title'] . '"', 'listing', (int) $listing['id']);
        }
    }

    /** Copies content, packages, add-ons and FAQ into a new draft. Images are not copied (each listing owns its files). */
    public static function duplicate(array $listing): string
    {
        return Db::transaction(static function () use ($listing): string {
            $now = now();
            $uuid = uuid4();
            $title = mb_substr('Cópia de ' . $listing['title'], 0, 90);
            $newId = Db::insert('listings', [
                'uuid' => $uuid,
                'creator_id' => (int) $listing['creator_id'],
                'category_id' => $listing['category_id'],
                'title' => $title,
                'slug' => self::uniqueSlug($title),
                'short_description' => $listing['short_description'],
                'description' => $listing['description'],
                'what_company_buys' => $listing['what_company_buys'],
                'what_company_provides' => $listing['what_company_provides'],
                'how_it_works' => $listing['how_it_works'],
                'framing' => $listing['framing'],
                'additional_info' => $listing['additional_info'],
                'video_url' => $listing['video_url'],
                'status' => 'draft',
                'wizard_step' => 2,
                'starting_price_cents' => $listing['starting_price_cents'],
                'min_delivery_days' => $listing['min_delivery_days'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $id = (int) $listing['id'];
            Db::run(
                'INSERT INTO listing_packages (listing_id, hours, price_cents, delivery_days, revisions, description, sort_order, created_at, updated_at)
                 SELECT :new, hours, price_cents, delivery_days, revisions, description, sort_order, :now, :now2 FROM listing_packages WHERE listing_id = :id',
                ['new' => $newId, 'now' => $now, 'now2' => $now, 'id' => $id]
            );
            Db::run(
                'INSERT INTO listing_addons (listing_id, name, description, price_cents, extra_days, sort_order, created_at, updated_at)
                 SELECT :new, name, description, price_cents, extra_days, sort_order, :now, :now2 FROM listing_addons WHERE listing_id = :id',
                ['new' => $newId, 'now' => $now, 'now2' => $now, 'id' => $id]
            );
            Db::run(
                'INSERT INTO listing_faqs (listing_id, question, answer, sort_order, created_at, updated_at)
                 SELECT :new, question, answer, sort_order, :now, :now2 FROM listing_faqs WHERE listing_id = :id',
                ['new' => $newId, 'now' => $now, 'now2' => $now, 'id' => $id]
            );
            Audit::activity((int) $listing['creator_id'], 'listing_duplicated', 'Duplicou "' . $listing['title'] . '"', 'listing', $newId);

            return $uuid;
        });
    }

    public static function deleteImage(array $image): void
    {
        Db::run('DELETE FROM listing_images WHERE id = :id', ['id' => (int) $image['id']]);
        Storage::delete((string) $image['path']);
        Storage::delete($image['thumb_path'] ?? null);
        self::syncCover((int) $image['listing_id']);
    }

    /** Keeps exactly one primary image and mirrors it into listings.cover_path for fast card queries. */
    public static function syncCover(int $listingId): void
    {
        $primary = Db::first('SELECT id, path, thumb_path FROM listing_images WHERE listing_id = :id ORDER BY is_primary DESC, sort_order, id LIMIT 1', ['id' => $listingId]);
        Db::run('UPDATE listing_images SET is_primary = 0 WHERE listing_id = :id', ['id' => $listingId]);
        if ($primary) {
            Db::run('UPDATE listing_images SET is_primary = 1 WHERE id = :id', ['id' => (int) $primary['id']]);
        }
        Db::update('listings', ['cover_path' => $primary ? ($primary['thumb_path'] ?: $primary['path']) : null], ['id' => $listingId]);
    }

    public static function logModeration(int $listingId, ?int $actorId, string $action, ?string $reason): void
    {
        Db::insert('listing_moderations', [
            'listing_id' => $listingId,
            'actor_id' => $actorId,
            'action' => $action,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    /** Only YouTube/Vimeo links are embedded; anything else is rejected at validation time. */
    public static function embedUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/' . $m[1];
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d{6,12})~', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        return null;
    }
}
