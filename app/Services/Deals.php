<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use App\Core\HttpException;

final class Deals
{
    public const EVENT_LABELS = [
        'created' => 'Contrato criado a partir da solicitação aceita',
        'payment_confirmed' => 'Pagamento confirmado',
        'scheduled' => 'Agenda atualizada',
        'started' => 'Gravação iniciada',
        'completed' => 'Contrato concluído',
        'payout' => 'Repasse ao criador registrado',
        'refunded' => 'Reembolso registrado',
        'cancelled' => 'Contrato cancelado',
        'attachment' => 'Arquivo anexado',
        'reviewed' => 'Empresa avaliou o contrato',
        'note' => 'Observação da administração',
    ];

    /** Pending requests past their deadline become "expired"; runs cheaply before listing requests. */
    public static function expireStale(): void
    {
        Db::run(
            "UPDATE requests SET status = 'expired', updated_at = :now WHERE status = 'pending' AND expires_at IS NOT NULL AND expires_at < :now2",
            ['now' => now(), 'now2' => now()]
        );
    }

    /**
     * Prices always come from the database, never from the form.
     * @param array<int, int> $addonIds
     */
    public static function createRequest(int $companyId, array $listing, int $packageId, array $addonIds, array $data): array
    {
        if ($listing['status'] !== 'active') {
            throw new HttpException(422, 'Este anúncio não está disponível para contratação.');
        }
        $package = Db::first('SELECT * FROM listing_packages WHERE id = :id AND listing_id = :l', ['id' => $packageId, 'l' => (int) $listing['id']]);
        if (!$package) {
            throw new HttpException(422, 'Escolha um dos pacotes deste anúncio.');
        }
        $addons = [];
        if ($addonIds !== []) {
            $placeholders = [];
            $params = ['l' => (int) $listing['id']];
            foreach (array_values(array_unique($addonIds)) as $i => $addonId) {
                $placeholders[] = ':a' . $i;
                $params['a' . $i] = $addonId;
            }
            $addons = Db::all('SELECT id, name, price_cents, extra_days FROM listing_addons WHERE listing_id = :l AND id IN (' . implode(',', $placeholders) . ')', $params);
            if (count($addons) !== count(array_unique($addonIds))) {
                throw new HttpException(422, 'Algum adicional escolhido não pertence a este anúncio.');
            }
        }
        $duplicate = Db::value(
            "SELECT 1 FROM requests WHERE company_id = :c AND listing_id = :l AND status = 'pending'",
            ['c' => $companyId, 'l' => (int) $listing['id']]
        );
        if ($duplicate) {
            throw new HttpException(422, 'Você já tem uma solicitação aguardando resposta para este anúncio.');
        }

        $addonsCents = array_sum(array_map(static fn (array $a): int => (int) $a['price_cents'], $addons));
        $total = (int) $package['price_cents'] + $addonsCents;

        return Db::transaction(static function () use ($companyId, $listing, $package, $addons, $addonsCents, $total, $data): array {
            $now = now();
            $conversationId = Messages::conversationFor($companyId, (int) $listing['creator_id'], (int) $listing['id']);
            $uuid = uuid4();
            $code = self::code('SOL');
            $id = Db::insert('requests', [
                'uuid' => $uuid,
                'code' => $code,
                'company_id' => $companyId,
                'creator_id' => (int) $listing['creator_id'],
                'listing_id' => (int) $listing['id'],
                'package_id' => (int) $package['id'],
                'conversation_id' => $conversationId,
                'hours' => (int) $package['hours'],
                'package_price_cents' => (int) $package['price_cents'],
                'addons_cents' => $addonsCents,
                'total_cents' => $total,
                'addons_json' => $addons !== [] ? json_encode($addons, JSON_UNESCAPED_UNICODE) : null,
                'desired_date' => $data['desired_date'] ?: null,
                'desired_time' => $data['desired_time'] ?: null,
                'theme' => $data['theme'],
                'briefing' => $data['briefing'] ?: null,
                'message' => $data['message'] ?: null,
                'status' => 'pending',
                'expires_at' => date('Y-m-d H:i:s', time() + Settings::int('request_expiry_hours') * 3600),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            if (!empty($data['message'])) {
                Messages::post($conversationId, $companyId, (string) $data['message']);
            }
            Db::run('UPDATE listings SET contacts_count = contacts_count + 1 WHERE id = :id', ['id' => (int) $listing['id']]);
            Notifier::send((int) $listing['creator_id'], 'request_new', 'Nova solicitação: ' . $code, (int) $package['hours'] . 'h em "' . $listing['title'] . '" · ' . money($total), '/painel/solicitacoes/' . $uuid);
            Audit::activity($companyId, 'request_created', 'Enviou a solicitação ' . $code, 'request', $id);

            return ['id' => $id, 'uuid' => $uuid, 'code' => $code];
        });
    }

    public static function acceptRequest(array $request, int $creatorId): string
    {
        self::assertPending($request);

        return Db::transaction(static function () use ($request, $creatorId): string {
            $now = now();
            $feePercent = max(0, min(50, Settings::int('platform_fee_percent')));
            $fee = (int) round((int) $request['total_cents'] * $feePercent / 100);
            $uuid = uuid4();
            $code = self::code('CTR');

            $changed = Db::run(
                "UPDATE requests SET status = 'accepted', responded_at = :now, updated_at = :now2 WHERE id = :id AND status = 'pending'",
                ['now' => $now, 'now2' => $now, 'id' => (int) $request['id']]
            )->rowCount();
            if ($changed === 0) {
                throw new HttpException(422, 'Esta solicitação já foi respondida.');
            }

            $contractId = Db::insert('contracts', [
                'uuid' => $uuid,
                'code' => $code,
                'request_id' => (int) $request['id'],
                'company_id' => (int) $request['company_id'],
                'creator_id' => (int) $request['creator_id'],
                'listing_id' => (int) $request['listing_id'],
                'package_id' => $request['package_id'],
                'conversation_id' => $request['conversation_id'],
                'hours' => (int) $request['hours'],
                'package_price_cents' => (int) $request['package_price_cents'],
                'addons_cents' => (int) $request['addons_cents'],
                'total_cents' => (int) $request['total_cents'],
                'fee_cents' => $fee,
                'creator_amount_cents' => (int) $request['total_cents'] - $fee,
                'addons_json' => $request['addons_json'],
                'scheduled_date' => $request['desired_date'],
                'scheduled_time' => $request['desired_time'],
                'theme' => $request['theme'],
                'briefing' => $request['briefing'],
                'status' => 'awaiting_payment',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            Db::insert('payments', [
                'uuid' => uuid4(),
                'contract_id' => $contractId,
                'company_id' => (int) $request['company_id'],
                'creator_id' => (int) $request['creator_id'],
                'amount_cents' => (int) $request['total_cents'],
                'fee_cents' => $fee,
                'net_cents' => (int) $request['total_cents'] - $fee,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            self::event($contractId, $creatorId, 'created', null);
            Notifier::send((int) $request['company_id'], 'request_accepted', 'Solicitação aceita: ' . $request['code'], 'O contrato ' . $code . ' foi criado e aguarda pagamento.', '/empresa/contratos/' . $uuid);
            Audit::activity($creatorId, 'request_accepted', 'Aceitou a solicitação ' . $request['code'], 'request', (int) $request['id']);

            return $uuid;
        });
    }

    public static function declineRequest(array $request, int $creatorId, string $reason): void
    {
        self::assertPending($request);
        Db::update('requests', ['status' => 'declined', 'decline_reason' => $reason, 'responded_at' => now(), 'updated_at' => now()], ['id' => (int) $request['id']]);
        Notifier::send((int) $request['company_id'], 'request_declined', 'Solicitação recusada: ' . $request['code'], $reason, '/empresa/solicitacoes/' . $request['uuid']);
        Audit::activity($creatorId, 'request_declined', 'Recusou a solicitação ' . $request['code'], 'request', (int) $request['id']);
    }

    public static function cancelRequest(array $request, int $companyId): void
    {
        self::assertPending($request);
        Db::update('requests', ['status' => 'cancelled', 'updated_at' => now()], ['id' => (int) $request['id']]);
        Notifier::send((int) $request['creator_id'], 'request_cancelled', 'Solicitação cancelada pela empresa: ' . $request['code'], null, '/painel/solicitacoes/' . $request['uuid']);
        Audit::activity($companyId, 'request_cancelled', 'Cancelou a solicitação ' . $request['code'], 'request', (int) $request['id']);
    }

    /** Called by the admin today and by a payment gateway webhook later. */
    public static function confirmPayment(array $contract, ?int $adminId, string $method, ?string $reference, ?string $gateway = null): void
    {
        if ($contract['status'] !== 'awaiting_payment') {
            throw new HttpException(422, 'Este contrato não está aguardando pagamento.');
        }
        Db::transaction(static function () use ($contract, $adminId, $method, $reference, $gateway): void {
            $now = now();
            Db::run(
                "UPDATE payments SET status = 'paid', method = :m, gateway = :g, gateway_reference = :r, paid_at = :now, updated_at = :now2 WHERE contract_id = :c AND status = 'pending'",
                ['m' => $method, 'g' => $gateway, 'r' => $reference, 'now' => $now, 'now2' => $now, 'c' => (int) $contract['id']]
            );
            Db::update('contracts', ['status' => 'confirmed', 'paid_at' => $now, 'confirmed_at' => $now, 'updated_at' => $now], ['id' => (int) $contract['id']]);
            self::event((int) $contract['id'], $adminId, 'payment_confirmed', $method . ($reference ? ' · ref. ' . $reference : ''));
        });
        Notifier::send((int) $contract['creator_id'], 'contract_confirmed', 'Contrato ' . $contract['code'] . ' confirmado', 'O pagamento foi confirmado. Combine os detalhes da gravação.', '/painel/contratos/' . $contract['uuid']);
        Notifier::send((int) $contract['company_id'], 'contract_confirmed', 'Pagamento confirmado: ' . $contract['code'], null, '/empresa/contratos/' . $contract['uuid']);
    }

    public static function start(array $contract, int $creatorId): void
    {
        self::transition($contract, 'confirmed', 'in_progress', ['started_at' => now()]);
        self::event((int) $contract['id'], $creatorId, 'started', null);
        Notifier::send((int) $contract['company_id'], 'contract_started', 'Contrato ' . $contract['code'] . ' em andamento', null, '/empresa/contratos/' . $contract['uuid']);
    }

    public static function complete(array $contract, int $creatorId, ?string $note): void
    {
        Db::transaction(static function () use ($contract, $creatorId, $note): void {
            self::transition($contract, 'in_progress', 'completed', ['completed_at' => now()]);
            Db::run("UPDATE payments SET status = 'awaiting_payout', updated_at = :now WHERE contract_id = :c AND status = 'paid'", ['now' => now(), 'c' => (int) $contract['id']]);
            Db::run('UPDATE creators SET contracts_count = contracts_count + 1, hours_sold = hours_sold + :h, updated_at = :now WHERE user_id = :u', ['h' => (int) $contract['hours'], 'now' => now(), 'u' => (int) $contract['creator_id']]);
            Db::run('UPDATE listings SET contracts_count = contracts_count + 1 WHERE id = :id', ['id' => (int) $contract['listing_id']]);
            self::event((int) $contract['id'], $creatorId, 'completed', $note);
        });
        Notifier::send((int) $contract['company_id'], 'contract_completed', 'Contrato ' . $contract['code'] . ' concluído', 'Conte como foi: a sua avaliação ajuda outras empresas.', '/empresa/contratos/' . $contract['uuid']);
    }

    public static function schedule(array $contract, int $actorId, ?string $date, ?string $time): void
    {
        if (!in_array($contract['status'], ['awaiting_payment', 'confirmed', 'in_progress'], true)) {
            throw new HttpException(422, 'Não é possível alterar a agenda deste contrato.');
        }
        Db::update('contracts', ['scheduled_date' => $date, 'scheduled_time' => $time, 'updated_at' => now()], ['id' => (int) $contract['id']]);
        self::event((int) $contract['id'], $actorId, 'scheduled', trim(fmt_date($date) . ' ' . ($time ?? '')));
        Notifier::send((int) $contract['company_id'], 'contract_scheduled', 'Agenda atualizada: ' . $contract['code'], trim(fmt_date($date) . ' ' . ($time ?? '')), '/empresa/contratos/' . $contract['uuid']);
    }

    /** Participants can cancel before payment; after payment only the admin cancels (and records the refund). */
    public static function cancel(array $contract, int $actorId, string $reason, bool $byAdmin): void
    {
        $allowed = $byAdmin ? ['awaiting_payment', 'confirmed', 'in_progress'] : ['awaiting_payment'];
        if (!in_array($contract['status'], $allowed, true)) {
            throw new HttpException(422, $byAdmin ? 'Este contrato não pode ser cancelado.' : 'Depois do pagamento, o cancelamento é feito pelo suporte.');
        }
        Db::transaction(static function () use ($contract, $actorId, $reason): void {
            $now = now();
            Db::update('contracts', ['status' => 'cancelled', 'cancelled_at' => $now, 'cancel_reason' => $reason, 'updated_at' => $now], ['id' => (int) $contract['id']]);
            Db::run("UPDATE payments SET status = 'cancelled', cancelled_at = :now, updated_at = :now2 WHERE contract_id = :c AND status = 'pending'", ['now' => $now, 'now2' => $now, 'c' => (int) $contract['id']]);
            Db::run("UPDATE payments SET status = 'refunded', refunded_at = :now, updated_at = :now2 WHERE contract_id = :c AND status = 'paid'", ['now' => $now, 'now2' => $now, 'c' => (int) $contract['id']]);
            self::event((int) $contract['id'], $actorId, 'cancelled', $reason);
        });
        foreach ([(int) $contract['company_id'] => '/empresa/contratos/', (int) $contract['creator_id'] => '/painel/contratos/'] as $userId => $prefix) {
            if ($userId !== $actorId) {
                Notifier::send($userId, 'contract_cancelled', 'Contrato ' . $contract['code'] . ' cancelado', $reason, $prefix . $contract['uuid']);
            }
        }
        if ($byAdmin) {
            Audit::admin('contract.cancel', 'contract', (int) $contract['id'], 'Cancelou o contrato ' . $contract['code'], ['reason' => $reason]);
        }
    }

    public static function registerPayout(array $payment, string $reference): void
    {
        if ($payment['status'] !== 'awaiting_payout') {
            throw new HttpException(422, 'Este pagamento não está aguardando repasse.');
        }
        Db::update('payments', ['status' => 'paid_out', 'paid_out_at' => now(), 'gateway_reference' => $reference ?: $payment['gateway_reference'], 'updated_at' => now()], ['id' => (int) $payment['id']]);
        self::event((int) $payment['contract_id'], \App\Core\Auth::id(), 'payout', $reference ?: null);
        Notifier::send((int) $payment['creator_id'], 'payout', 'Repasse registrado', money($payment['net_cents']) . ' referente ao seu contrato.', '/painel/financeiro');
        Audit::admin('payment.payout', 'payment', (int) $payment['id'], 'Registrou repasse de ' . money($payment['net_cents']), ['reference' => $reference]);
    }

    public static function review(array $contract, int $companyId, int $rating, string $comment): void
    {
        if ($contract['status'] !== 'completed') {
            throw new HttpException(422, 'Só contratos concluídos podem ser avaliados.');
        }
        if (Db::value('SELECT 1 FROM reviews WHERE contract_id = :c', ['c' => (int) $contract['id']])) {
            throw new HttpException(422, 'Este contrato já foi avaliado.');
        }
        Db::insert('reviews', [
            'contract_id' => (int) $contract['id'],
            'listing_id' => (int) $contract['listing_id'],
            'creator_id' => (int) $contract['creator_id'],
            'company_id' => $companyId,
            'rating' => $rating,
            'comment' => $comment,
            'status' => 'visible',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        self::refreshRatings((int) $contract['listing_id'], (int) $contract['creator_id']);
        self::event((int) $contract['id'], $companyId, 'reviewed', $rating . ' de 5');
        Notifier::send((int) $contract['creator_id'], 'review_new', 'Você recebeu uma avaliação', $rating . ' de 5 no contrato ' . $contract['code'], '/painel/contratos/' . $contract['uuid']);
    }

    public static function refreshRatings(int $listingId, int $creatorId): void
    {
        $listing = Db::first("SELECT COALESCE(AVG(rating), 0) AS avg, COUNT(*) AS total FROM reviews WHERE listing_id = :l AND status = 'visible'", ['l' => $listingId]);
        Db::update('listings', ['rating_avg' => round((float) $listing['avg'], 2), 'rating_count' => (int) $listing['total']], ['id' => $listingId]);
        $creator = Db::first("SELECT COALESCE(AVG(rating), 0) AS avg, COUNT(*) AS total FROM reviews WHERE creator_id = :c AND status = 'visible'", ['c' => $creatorId]);
        Db::run('UPDATE creators SET rating_avg = :a, rating_count = :t WHERE user_id = :u', ['a' => round((float) $creator['avg'], 2), 't' => (int) $creator['total'], 'u' => $creatorId]);
    }

    public static function event(int $contractId, ?int $actorId, string $type, ?string $note): void
    {
        Db::insert('contract_events', [
            'contract_id' => $contractId,
            'actor_id' => $actorId,
            'type' => $type,
            'note' => $note !== null ? mb_substr($note, 0, 1000) : null,
            'created_at' => now(),
        ]);
    }

    private static function transition(array $contract, string $from, string $to, array $extra): void
    {
        $sets = ['status' => $to, 'updated_at' => now()] + $extra;
        $changed = Db::run(
            'UPDATE contracts SET ' . implode(', ', array_map(static fn (string $k): string => "{$k} = :{$k}", array_keys($sets))) . ' WHERE id = :id AND status = :from',
            $sets + ['id' => (int) $contract['id'], 'from' => $from]
        )->rowCount();
        if ($changed === 0) {
            throw new HttpException(422, 'O contrato mudou de situação. Recarregue a página.');
        }
    }

    private static function assertPending(array $request): void
    {
        if ($request['status'] !== 'pending') {
            throw new HttpException(422, 'Esta solicitação já foi respondida.');
        }
        if ($request['expires_at'] && strtotime((string) $request['expires_at']) < time()) {
            self::expireStale();
            throw new HttpException(422, 'O prazo de resposta desta solicitação terminou.');
        }
    }

    private static function code(string $prefix): string
    {
        return $prefix . '-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}
