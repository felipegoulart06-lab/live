<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

final class Messages
{
    public static function conversationFor(int $companyId, int $creatorId, ?int $listingId): int
    {
        $existing = Db::value(
            'SELECT id FROM conversations WHERE company_id = :c AND creator_id = :r AND ' . ($listingId ? 'listing_id = :l' : 'listing_id IS NULL'),
            array_filter(['c' => $companyId, 'r' => $creatorId, 'l' => $listingId])
        );
        if ($existing) {
            return (int) $existing;
        }

        return Db::insert('conversations', [
            'uuid' => uuid4(),
            'company_id' => $companyId,
            'creator_id' => $creatorId,
            'listing_id' => $listingId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function post(int $conversationId, int $senderId, string $body): void
    {
        $conversation = Db::first('SELECT * FROM conversations WHERE id = :id', ['id' => $conversationId]);
        if (!$conversation) {
            return;
        }
        $now = now();
        $isCompany = (int) $conversation['company_id'] === $senderId;
        $recipient = $isCompany ? (int) $conversation['creator_id'] : (int) $conversation['company_id'];
        $recipientRead = $isCompany ? $conversation['creator_read_at'] : $conversation['company_read_at'];
        $recipientWasUpToDate = !$conversation['last_message_at'] || ($recipientRead && $recipientRead >= $conversation['last_message_at']);

        Db::insert('messages', ['conversation_id' => $conversationId, 'sender_id' => $senderId, 'body' => $body, 'created_at' => $now]);
        Db::update('conversations', [
            'last_message_at' => $now,
            ($isCompany ? 'company_read_at' : 'creator_read_at') => $now,
            'updated_at' => $now,
        ], ['id' => $conversationId]);

        // One notification per unread streak, not one per message.
        if ($recipientWasUpToDate) {
            $name = (string) Db::value('SELECT display_name FROM profiles WHERE user_id = :u', ['u' => $senderId]);
            $prefix = $isCompany ? '/painel/mensagens/' : '/empresa/mensagens/';
            Notifier::send($recipient, 'message_new', 'Nova mensagem de ' . $name, mb_substr($body, 0, 140), $prefix . $conversation['uuid']);
        }
    }

    public static function markRead(array $conversation, int $userId): void
    {
        $column = (int) $conversation['company_id'] === $userId ? 'company_read_at' : 'creator_read_at';
        Db::update('conversations', [$column => now()], ['id' => (int) $conversation['id']]);
    }

    public static function unreadCount(int $userId, string $role): int
    {
        [$mine, $read] = $role === 'creator' ? ['creator_id', 'creator_read_at'] : ['company_id', 'company_read_at'];

        return (int) Db::value(
            "SELECT COUNT(*) FROM conversations WHERE {$mine} = :u AND last_message_at IS NOT NULL AND ({$read} IS NULL OR {$read} < last_message_at)",
            ['u' => $userId]
        );
    }
}
