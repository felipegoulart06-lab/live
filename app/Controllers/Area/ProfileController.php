<?php

declare(strict_types=1);

namespace App\Controllers\Area;

use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Storage;
use App\Services\Accounts;
use App\Services\Audit;
use App\Services\Notifier;

final class ProfileController extends AreaController
{
    private const LABELS = [
        'display_name' => 'Nome',
        'headline' => 'Título profissional',
        'bio' => 'Biografia',
        'city' => 'Cidade',
        'state' => 'UF',
        'phone' => 'Telefone',
        'experience_years' => 'Anos de experiência',
        'specialties' => 'Especialidades',
        'company_name' => 'Nome da empresa',
        'legal_name' => 'Razão social',
        'document' => 'CNPJ',
        'responsible_name' => 'Responsável',
        'website' => 'Site',
        'email' => 'E-mail',
        'current_password' => 'Senha atual',
        'password' => 'Nova senha',
        'min_notice_hours' => 'Antecedência mínima',
        'unavailable_from' => 'Indisponível de',
        'unavailable_until' => 'Indisponível até',
        'unavailable_note' => 'Observação',
    ];

    public function edit(Request $request): Response
    {
        $user = $this->user();
        $profile = Db::first('SELECT p.*, u.email FROM profiles p JOIN users u ON u.id = p.user_id WHERE p.user_id = :u', ['u' => $user->id]);
        $data = ['title' => 'Perfil', 'profile' => $profile];
        if ($user->isCreator()) {
            $data['creator'] = Db::first('SELECT * FROM creators WHERE user_id = :u', ['u' => $user->id]);
            $slots = [];
            foreach (Db::all('SELECT weekday, start_time, end_time FROM availability WHERE creator_id = :u', ['u' => $user->id]) as $row) {
                $slots[(int) $row['weekday']] = $row;
            }
            $data['slots'] = $slots;
        } else {
            $data['company'] = Db::first('SELECT * FROM companies WHERE user_id = :u', ['u' => $user->id]);
        }

        return $this->render('area/profile', $data);
    }

    public function update(Request $request): Response
    {
        $user = $this->user();
        $back = $this->area() . '/perfil';
        $input = $request->all();

        if ($user->isCreator()) {
            $data = $this->pick($input, ['display_name', 'headline', 'bio', 'city', 'state', 'phone', 'experience_years', 'specialties']);
            $rules = [
                'display_name' => 'required|min:3|max:80',
                'headline' => 'max:120',
                'bio' => 'max:2000',
                'city' => 'max:80',
                'state' => 'max:2',
                'phone' => 'max:30',
                'experience_years' => 'between:0,60',
                'specialties' => 'max:300',
            ];
        } else {
            $data = $this->pick($input, ['display_name', 'company_name', 'legal_name', 'document', 'responsible_name', 'phone', 'city', 'state', 'website']);
            $rules = [
                'display_name' => 'required|min:3|max:80',
                'company_name' => 'required|min:2|max:120',
                'legal_name' => 'max:160',
                'document' => 'max:20',
                'responsible_name' => 'required|min:3|max:80',
                'phone' => 'max:30',
                'city' => 'max:80',
                'state' => 'max:2',
                'website' => 'url|max:200',
            ];
        }
        if ($response = $this->invalid($data, $rules, self::LABELS, $back)) {
            return $response;
        }

        $data = array_map(static fn (string $v): ?string => $v === '' ? null : $v, $data);
        $now = now();
        Db::transaction(function () use ($user, $data, $now): void {
            $profile = [
                'display_name' => (string) $data['display_name'],
                'phone' => $data['phone'],
                'city' => $data['city'],
                'state' => $data['state'] !== null ? mb_strtoupper((string) $data['state']) : null,
                'updated_at' => $now,
            ];
            if ($user->displayName !== $data['display_name']) {
                $profile['slug'] = Accounts::uniqueSlug((string) $data['display_name'], $user->id);
            }
            if ($user->isCreator()) {
                $profile += [
                    'headline' => $data['headline'],
                    'bio' => $data['bio'],
                    'experience_years' => $data['experience_years'] !== null ? (int) $data['experience_years'] : null,
                    'specialties' => $data['specialties'],
                ];
            } else {
                Db::update('companies', [
                    'company_name' => (string) $data['company_name'],
                    'legal_name' => $data['legal_name'],
                    'document' => $data['document'],
                    'responsible_name' => (string) $data['responsible_name'],
                    'phone' => $data['phone'],
                    'city' => $data['city'],
                    'state' => $profile['state'],
                    'website' => $data['website'],
                    'updated_at' => $now,
                ], ['user_id' => $user->id]);
            }
            Db::update('profiles', $profile, ['user_id' => $user->id]);
        });
        Audit::activity($user->id, 'profile_updated', 'Atualizou o perfil');
        $this->success('Perfil atualizado.');

        return $this->redirect($back);
    }

    public function avatar(Request $request): Response
    {
        $user = $this->user();
        $this->throttle('upload:' . $user->id, 60, 3600);
        $file = $request->files()['avatar'] ?? [];
        $stored = Storage::store(is_array($file) ? $file : [], 'avatars/' . substr($user->uuid, 0, 8), true);
        $old = Db::value('SELECT avatar_path FROM profiles WHERE user_id = :u', ['u' => $user->id]);
        Db::update('profiles', ['avatar_path' => $stored['thumb_path'] ?? $stored['path'], 'updated_at' => now()], ['user_id' => $user->id]);
        if (is_string($old)) {
            Storage::delete($old);
        }
        $this->success('Foto atualizada.');

        return $this->redirect($this->area() . '/perfil');
    }

    public function availability(Request $request): Response
    {
        $user = $this->user();
        $back = '/painel/perfil#disponibilidade';
        $days = (array) $request->input('day', []);
        $starts = (array) $request->input('start', []);
        $ends = (array) $request->input('end', []);
        $slots = [];
        for ($d = 0; $d <= 6; $d++) {
            if (empty($days[$d])) {
                continue;
            }
            $start = (string) ($starts[$d] ?? '');
            $end = (string) ($ends[$d] ?? '');
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $start) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $end) || $start >= $end) {
                $this->error(weekday_name($d) . ': informe início e fim válidos, com o fim depois do início.');

                return $this->redirect($back);
            }
            $slots[$d] = [$start, $end];
        }

        $extra = [
            'min_notice_hours' => (string) $request->input('min_notice_hours', '48'),
            'unavailable_from' => (string) $request->input('unavailable_from', ''),
            'unavailable_until' => (string) $request->input('unavailable_until', ''),
            'unavailable_note' => trim((string) $request->input('unavailable_note', '')),
        ];
        if ($response = $this->invalid($extra, [
            'min_notice_hours' => 'required|between:0,720',
            'unavailable_from' => 'date',
            'unavailable_until' => 'date',
            'unavailable_note' => 'max:200',
        ], self::LABELS, $back)) {
            return $response;
        }
        if (($extra['unavailable_from'] === '') !== ($extra['unavailable_until'] === '') || ($extra['unavailable_from'] !== '' && $extra['unavailable_from'] > $extra['unavailable_until'])) {
            $this->error('Para marcar ausência, informe as duas datas, com a final depois da inicial.');

            return $this->redirect($back);
        }

        Db::transaction(static function () use ($user, $slots, $extra): void {
            Db::run('DELETE FROM availability WHERE creator_id = :u', ['u' => $user->id]);
            foreach ($slots as $day => [$start, $end]) {
                Db::insert('availability', ['creator_id' => $user->id, 'weekday' => $day, 'start_time' => $start, 'end_time' => $end, 'created_at' => now(), 'updated_at' => now()]);
            }
            Db::update('creators', [
                'min_notice_hours' => (int) $extra['min_notice_hours'],
                'unavailable_from' => $extra['unavailable_from'] ?: null,
                'unavailable_until' => $extra['unavailable_until'] ?: null,
                'unavailable_note' => $extra['unavailable_note'] ?: null,
                'updated_at' => now(),
            ], ['user_id' => $user->id]);
        });
        $this->success('Disponibilidade atualizada.');

        return $this->redirect($back);
    }

    public function requestVerification(Request $request): Response
    {
        $user = $this->user();
        $creator = Db::first('SELECT is_verified, verification_requested_at FROM creators WHERE user_id = :u', ['u' => $user->id]);
        if ((int) $creator['is_verified'] === 1 || $creator['verification_requested_at']) {
            $this->error($creator['is_verified'] ? 'Seu perfil já é verificado.' : 'Seu pedido de verificação já está em análise.');

            return $this->redirect('/painel/perfil');
        }
        Db::update('creators', ['verification_requested_at' => now(), 'updated_at' => now()], ['user_id' => $user->id]);
        Notifier::admins('verification_request', 'Pedido de verificação de criador', $user->displayName, '/admin/criadores/' . $user->uuid);
        $this->success('Pedido de verificação enviado para a equipe.');

        return $this->redirect('/painel/perfil');
    }

    public function security(Request $request): Response
    {
        $user = $this->user();
        $this->throttle('password-change:' . $user->id, 6, 900);
        $back = $this->area() . '/perfil#seguranca';
        $data = $request->all();
        if ($response = $this->invalid($data, [
            'current_password' => 'required',
            'email' => 'required|email',
            'password' => 'password|confirmed|max:120',
        ], self::LABELS, $back)) {
            return $response;
        }
        $hash = (string) Db::value('SELECT password_hash FROM users WHERE id = :id', ['id' => $user->id]);
        if (!password_verify((string) $data['current_password'], $hash)) {
            Session::flash('errors', ['current_password' => ['Senha atual incorreta.']]);

            return $this->redirect($back);
        }
        $email = mb_strtolower(trim((string) $data['email']));
        if (Accounts::emailTaken($email, $user->id)) {
            Session::flash('errors', ['email' => ['Este e-mail já está em uso.']]);

            return $this->redirect($back);
        }
        $update = ['email' => $email, 'updated_at' => now()];
        if (!empty($data['password'])) {
            $update['password_hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        }
        Db::update('users', $update, ['id' => $user->id]);
        if (!empty($data['password'])) {
            Db::run('DELETE FROM sessions WHERE user_id = :u AND id <> :sid', ['u' => $user->id, 'sid' => session_id()]);
        }
        Audit::activity($user->id, 'security_updated', !empty($data['password']) ? 'Alterou a senha' : 'Atualizou o e-mail de acesso');
        $this->success('Dados de acesso atualizados.');

        return $this->redirect($back);
    }

    /** @param array<string, mixed> $input @param array<int, string> $keys @return array<string, string> */
    private function pick(array $input, array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = trim((string) ($input[$key] ?? ''));
        }

        return $out;
    }
}
