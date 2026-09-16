<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Uploader;
use App\Repositories\FreelancerAdminRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\UserRepository;
use App\Validators\Validator;
use PDOException;

final class FreelancerAdminService
{
    public function __construct(
        private readonly FreelancerAdminRepository $freelancers = new FreelancerAdminRepository(),
        private readonly UserRepository $users = new UserRepository(),
        private readonly ProfileRepository $profiles = new ProfileRepository()
    ) {
    }

    /** @param array<string, mixed> $input @return array{ok:bool, errors?:array, id?:int, message?:string} */
    public function create(array $input, array $files): array
    {
        $validator = new Validator();
        $ok = $validator->validate($input, [
            'display_name' => 'required|min:3|max:120',
            'email' => 'required|email|max:190',
            'password' => 'required|min:8|max:120',
            'status' => 'required|in:pending,active,suspended,banned',
        ]);
        if (!$ok) {
            return ['ok' => false, 'errors' => $validator->errors()];
        }

        $email = strtolower(trim((string) $input['email']));
        if ($this->freelancers->emailTaken($email)) {
            return ['ok' => false, 'errors' => ['email' => ['Este e-mail já está cadastrado.']]];
        }

        $name = trim((string) $input['display_name']);
        $slug = $this->uniqueSlug((string) ($input['slug'] ?? '') ?: $name);

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $userId = $this->users->create([
                'uuid' => nexo_uuid(),
                'email' => $email,
                'password' => password_hash((string) $input['password'], PASSWORD_DEFAULT),
                'account_type' => 'seller',
                'status' => (string) $input['status'],
                'email_verified_at' => !empty($input['email_verified']) ? now() : null,
            ]);

            $roleId = $this->users->roleIdBySlug('freelancer');
            if ($roleId) {
                $this->users->attachRole($userId, $roleId);
            }

            $avatar = $this->avatarFromFiles($files);
            $this->profiles->create(array_filter([
                'user_id' => $userId,
                'display_name' => $name,
                'professional_name' => trim((string) ($input['professional_name'] ?? '')) ?: $name,
                'slug' => $slug,
                'headline' => trim((string) ($input['headline'] ?? '')) ?: null,
                'bio' => trim((string) ($input['bio'] ?? '')) ?: null,
                'phone' => trim((string) ($input['phone'] ?? '')) ?: null,
                'city' => trim((string) ($input['city'] ?? '')) ?: null,
                'state' => trim((string) ($input['state'] ?? '')) ?: null,
                'country' => strtoupper(substr(trim((string) ($input['country'] ?? 'BR')), 0, 2)) ?: 'BR',
                'avatar_path' => $avatar,
                'experience_years' => $this->optionalInt($input['experience_years'] ?? null),
                'is_verified' => !empty($input['is_verified']) ? 1 : 0,
            ], static fn ($v) => $v !== null));

            $this->createWallet($userId);
            $this->syncExtras($userId, $input);
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            Logger::error('admin.freelancer.create', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'Não foi possível criar o profissional.'];
        }

        $this->audit('create', $userId, null, ['email' => $email, 'name' => $name]);
        Logger::info('admin.freelancer.created', ['id' => $userId, 'admin' => Auth::id()]);

        return ['ok' => true, 'id' => $userId];
    }

    /** @param array<string, mixed> $input @return array{ok:bool, errors?:array, message?:string} */
    public function update(int $id, array $input, array $files): array
    {
        $current = $this->freelancers->findForAdmin($id);
        if (!$current || empty($current['is_freelancer'])) {
            return ['ok' => false, 'message' => 'Profissional não encontrado.'];
        }

        $validator = new Validator();
        $rules = [
            'display_name' => 'required|min:3|max:120',
            'email' => 'required|email|max:190',
            'status' => 'required|in:pending,active,suspended,banned',
        ];
        if (trim((string) ($input['password'] ?? '')) !== '') {
            $rules['password'] = 'min:8|max:120';
        }
        if (!$validator->validate($input, $rules)) {
            return ['ok' => false, 'errors' => $validator->errors()];
        }

        $email = strtolower(trim((string) $input['email']));
        if ($this->freelancers->emailTaken($email, $id)) {
            return ['ok' => false, 'errors' => ['email' => ['Este e-mail já está em uso.']]];
        }

        $slug = slugify(trim((string) ($input['slug'] ?? '')) ?: (string) $input['display_name']) ?: 'profissional';
        if ($this->freelancers->slugTaken($slug, $id)) {
            return ['ok' => false, 'errors' => ['slug' => ['Este slug já está em uso.']]];
        }

        $userData = [
            'email' => $email,
            'status' => (string) $input['status'],
            'account_type' => (($input['also_buyer'] ?? '') === '1') ? 'both' : 'seller',
        ];
        if (!empty($input['email_verified'])) {
            $userData['email_verified_at'] = $current['email_verified_at'] ?: now();
        }
        if (trim((string) ($input['password'] ?? '')) !== '') {
            $userData['password'] = password_hash((string) $input['password'], PASSWORD_DEFAULT);
        }

        $profileData = [
            'display_name' => trim((string) $input['display_name']),
            'professional_name' => trim((string) ($input['professional_name'] ?? '')) ?: trim((string) $input['display_name']),
            'slug' => $slug,
            'headline' => trim((string) ($input['headline'] ?? '')) ?: null,
            'bio' => trim((string) ($input['bio'] ?? '')) ?: null,
            'phone' => trim((string) ($input['phone'] ?? '')) ?: null,
            'city' => trim((string) ($input['city'] ?? '')) ?: null,
            'state' => trim((string) ($input['state'] ?? '')) ?: null,
            'country' => strtoupper(substr(trim((string) ($input['country'] ?? 'BR')), 0, 2)) ?: 'BR',
            'experience_years' => $this->optionalInt($input['experience_years'] ?? null),
            'is_verified' => !empty($input['is_verified']) ? 1 : 0,
        ];
        $avatar = $this->avatarFromFiles($files);
        if ($avatar) {
            $profileData['avatar_path'] = $avatar;
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $this->users->updateById($id, $userData);
            $this->updateProfile((int) $current['profile_id'], $profileData);
            $this->syncExtras($id, $input);
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            Logger::error('admin.freelancer.update', ['id' => $id]);

            return ['ok' => false, 'message' => 'Não foi possível salvar as alterações.'];
        }

        $this->audit('update', $id, $current, $input);
        Logger::info('admin.freelancer.updated', ['id' => $id, 'admin' => Auth::id()]);

        return ['ok' => true];
    }

    /** @return array{ok:bool, message:string} */
    public function deactivate(int $id): array
    {
        $current = $this->freelancers->findForAdmin($id);
        if (!$current || empty($current['is_freelancer'])) {
            return ['ok' => false, 'message' => 'Profissional não encontrado.'];
        }

        $this->users->updateById($id, ['status' => 'suspended']);
        $this->audit('deactivate', $id, ['status' => $current['status']], ['status' => 'suspended']);

        return ['ok' => true, 'message' => 'Profissional desativado.'];
    }

    private function uniqueSlug(string $name): string
    {
        $base = slugify($name) ?: 'profissional';
        $slug = $base;
        $i = 1;
        while ($this->freelancers->slugTaken($slug)) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    /** @param array<string, mixed> $files */
    private function avatarFromFiles(array $files): ?string
    {
        if (empty($files['avatar']) || !is_array($files['avatar'])) {
            return null;
        }

        return Uploader::storePublicImage($files['avatar'], 'avatars');
    }

    private function optionalInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /** @param array<string, mixed> $data */
    private function updateProfile(int $profileId, array $data): void
    {
        $data['updated_at'] = now();
        $sets = [];
        $params = ['id' => $profileId];
        foreach ($data as $key => $value) {
            if (!preg_match('/^[a-z_]+$/', $key)) {
                continue;
            }
            $sets[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }
        $sql = 'UPDATE profiles SET ' . implode(', ', $sets) . ' WHERE id = :id';
        Database::pdo()->prepare($sql)->execute($params);
    }

    /** @param array<string, mixed> $input */
    private function syncExtras(int $userId, array $input): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM user_skills WHERE user_id = :id')->execute(['id' => $userId]);
        $names = preg_split('/[,;\n]+/', (string) ($input['skills'] ?? '')) ?: [];
        $insSkill = $pdo->prepare('INSERT OR IGNORE INTO skills (name, slug, created_at) VALUES (:n, :s, :c)');
        $findSkill = $pdo->prepare('SELECT id FROM skills WHERE slug = :s LIMIT 1');
        $link = $pdo->prepare('INSERT OR IGNORE INTO user_skills (user_id, skill_id) VALUES (:u, :s)');
        foreach ($names as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }
            $slug = slugify($name) ?: substr(md5($name), 0, 8);
            $insSkill->execute(['n' => $name, 's' => $slug, 'c' => now()]);
            $findSkill->execute(['s' => $slug]);
            $skillId = $findSkill->fetchColumn();
            if ($skillId) {
                $link->execute(['u' => $userId, 's' => $skillId]);
            }
        }

        $pdo->prepare('DELETE FROM user_badges WHERE user_id = :id')->execute(['id' => $userId]);
        $badgeIds = $input['badge_ids'] ?? [];
        if (!is_array($badgeIds)) {
            $badgeIds = [];
        }
        $insBadge = $pdo->prepare('INSERT OR IGNORE INTO user_badges (user_id, badge_id, awarded_at) VALUES (:u, :b, :a)');
        foreach ($badgeIds as $badgeId) {
            if (ctype_digit((string) $badgeId)) {
                $insBadge->execute(['u' => $userId, 'b' => (int) $badgeId, 'a' => now()]);
            }
        }

        $pdo->prepare("DELETE FROM commission_rules WHERE scope = 'user' AND user_id = :id")->execute(['id' => $userId]);
        $percent = trim((string) ($input['commission_percent'] ?? ''));
        if ($percent !== '' && is_numeric($percent)) {
            $stmt = $pdo->prepare(
                "INSERT INTO commission_rules (scope, user_id, percent, fixed_cents, withdraw_fee_cents, is_active, created_at, updated_at)
                 VALUES ('user', :user_id, :percent, 0, 0, 1, :c, :u)"
            );
            $stmt->execute([
                'user_id' => $userId,
                'percent' => (float) $percent,
                'c' => now(),
                'u' => now(),
            ]);
        }
    }

    private function createWallet(int $userId): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO wallets (user_id, available_cents, pending_cents, reserved_cents, currency, created_at, updated_at)
             VALUES (:user_id, 0, 0, 0, :currency, :created_at, :updated_at)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'currency' => env('APP_CURRENCY', 'BRL'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function audit(string $action, int $id, mixed $old, mixed $new): void
    {
        $adminId = Auth::id();
        if (!$adminId) {
            return;
        }
        $stmt = Database::pdo()->prepare(
            'INSERT INTO audit_logs (admin_id, action, object_type, object_id, old_values, new_values, ip_address, created_at)
             VALUES (:admin_id, :action, :object_type, :object_id, :old_values, :new_values, :ip_address, :created_at)'
        );
        $stmt->execute([
            'admin_id' => $adminId,
            'action' => $action,
            'object_type' => 'freelancer',
            'object_id' => $id,
            'old_values' => $old ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
            'new_values' => json_encode($this->safeNew($new), JSON_UNESCAPED_UNICODE),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'created_at' => now(),
        ]);
    }

    private function safeNew(mixed $new): mixed
    {
        if (!is_array($new)) {
            return $new;
        }
        unset($new['password'], $new['_csrf']);

        return $new;
    }
}
