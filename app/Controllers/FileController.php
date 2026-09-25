<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Storage;
use App\Services\Audit;

/** Private attachments are streamed through here after an ownership check; they have no public URL. */
final class FileController extends Controller
{
    public function download(Request $request): Response
    {
        $user = $this->user();
        $file = Db::first('SELECT * FROM attachments WHERE uuid = :u', ['u' => (string) $request->param('uuid')]);
        if (!$file) {
            throw HttpException::notFound('Arquivo não encontrado.');
        }

        $table = match ($file['context']) {
            'contract' => 'contracts',
            'request' => 'requests',
            default => 'conversations',
        };
        $parent = Db::first("SELECT company_id, creator_id FROM {$table} WHERE id = :id", ['id' => (int) $file['context_id']]);
        $isParticipant = $parent && in_array($user->id, [(int) $parent['company_id'], (int) $parent['creator_id']], true);
        if (!$isParticipant && !$user->isAdmin()) {
            throw HttpException::notFound('Arquivo não encontrado.');
        }
        if (!$isParticipant) {
            Audit::admin('attachment.view', 'attachment', (int) $file['id'], 'Abriu o arquivo "' . $file['original_name'] . '" para suporte');
        }

        $contents = Storage::read((string) $file['path'], true);
        if ($contents === null) {
            throw HttpException::notFound('O arquivo não está mais disponível.');
        }
        $inline = str_starts_with((string) $file['mime'], 'image/') || $file['mime'] === 'application/pdf';

        return Response::file($contents, (string) $file['mime'], (string) $file['original_name'], $inline);
    }
}
