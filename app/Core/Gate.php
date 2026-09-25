<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * Ownership rules. Every lookup by UUID goes through here so a guessed or copied ID from another
 * account resolves to 404, never to someone else's data.
 */
final class Gate
{
    public static function user(): User
    {
        $user = Auth::user();
        if (!$user) {
            throw HttpException::forbidden('Entre na sua conta para continuar.');
        }

        return $user;
    }

    public static function master(): User
    {
        $user = self::user();
        if (!$user->isMaster()) {
            throw HttpException::forbidden('Apenas o administrador master pode fazer isso.');
        }

        return $user;
    }

    /** Listing owned by the signed-in creator. @return array<string, mixed> */
    public static function ownListing(string $uuid): array
    {
        $user = self::user();
        $row = Db::first(
            'SELECT * FROM listings WHERE uuid = :uuid AND creator_id = :uid AND deleted_at IS NULL',
            ['uuid' => $uuid, 'uid' => $user->id]
        );

        return $row ?? throw HttpException::notFound('Anúncio não encontrado.');
    }

    /** Request where the signed-in user is the creator or the company. @return array<string, mixed> */
    public static function ownRequest(string $uuid): array
    {
        return self::participantRow('requests', $uuid, 'Solicitação não encontrada.');
    }

    /** @return array<string, mixed> */
    public static function ownContract(string $uuid): array
    {
        return self::participantRow('contracts', $uuid, 'Contrato não encontrado.');
    }

    /** @return array<string, mixed> */
    public static function ownConversation(string $uuid): array
    {
        return self::participantRow('conversations', $uuid, 'Conversa não encontrada.');
    }

    /** @return array<string, mixed> */
    private static function participantRow(string $table, string $uuid, string $missing): array
    {
        $user = self::user();
        $column = match ($user->role) {
            'creator' => 'creator_id',
            'company' => 'company_id',
            default => throw HttpException::notFound($missing),
        };
        $row = Db::first("SELECT * FROM {$table} WHERE uuid = :uuid AND {$column} = :uid", ['uuid' => $uuid, 'uid' => $user->id]);

        return $row ?? throw HttpException::notFound($missing);
    }
}
