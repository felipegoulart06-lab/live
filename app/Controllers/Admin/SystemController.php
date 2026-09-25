<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Db;
use App\Core\Gate;
use App\Core\Request;
use App\Core\Response;
use App\Services\Audit;
use App\Services\Settings;

final class SystemController extends AdminController
{
    private const FIELDS = [
        'platform_name' => ['Nome da plataforma', 'required|min:2|max:60'],
        'support_email' => ['E-mail de suporte', 'required|email'],
        'meta_description' => ['Descrição para buscadores (SEO)', 'required|min:30|max:160'],
        'platform_fee_percent' => ['Taxa da plataforma (%)', 'required|between:0,50'],
        'request_expiry_hours' => ['Prazo para o criador responder (horas)', 'required|between:12,336'],
        'min_package_hours' => ['Mínimo de horas por pacote', 'required|between:1,24'],
        'max_package_hours' => ['Máximo de horas por pacote', 'required|between:1,24'],
        'max_packages_per_listing' => ['Máximo de pacotes por anúncio', 'required|between:1,10'],
        'max_images_per_listing' => ['Máximo de fotos por anúncio', 'required|between:1,20'],
        'require_listing_approval' => ['Novos anúncios passam por aprovação', 'required|in:0,1'],
        'maintenance_mode' => ['Modo manutenção', 'required|in:0,1'],
    ];

    public function settings(Request $request): Response
    {
        $values = [];
        foreach (array_keys(self::FIELDS) as $key) {
            $values[$key] = (string) Settings::get($key);
        }

        return $this->render('admin/system/settings', [
            'title' => 'Configurações',
            'fields' => self::FIELDS,
            'values' => $values,
            'canEdit' => $this->user()->isMaster(),
            'storageReady' => \App\Core\Storage::available(),
            'database' => \App\Core\Database::isPostgres() ? 'PostgreSQL (Supabase)' : 'SQLite local',
        ]);
    }

    public function saveSettings(Request $request): Response
    {
        Gate::master();
        $data = [];
        $rules = [];
        $labels = [];
        foreach (self::FIELDS as $key => [$label, $rule]) {
            $data[$key] = trim((string) $request->input($key, ''));
            $rules[$key] = $rule;
            $labels[$key] = $label;
        }
        if ($response = $this->invalid($data, $rules, $labels, '/admin/configuracoes')) {
            return $response;
        }
        if ((int) $data['min_package_hours'] > (int) $data['max_package_hours']) {
            $this->error('O mínimo de horas não pode ser maior que o máximo.');

            return $this->redirect('/admin/configuracoes');
        }
        $changed = [];
        foreach ($data as $key => $value) {
            if ((string) Settings::get($key) !== $value) {
                $changed[$key] = ['de' => (string) Settings::get($key), 'para' => $value];
            }
        }
        Settings::putMany($data);
        Audit::admin('settings.update', 'settings', null, 'Alterou configurações da plataforma', $changed);
        $this->success('Configurações salvas.');

        return $this->redirect('/admin/configuracoes');
    }

    public function activity(Request $request): Response
    {
        $tab = $request->query('aba') === 'usuarios' ? 'usuarios' : 'admin';
        $q = trim((string) $request->query('q', ''));
        [$from, $to] = $this->period($request);
        $params = [];
        $where = ['1 = 1'];
        $alias = $tab === 'admin' ? 'a' : 'g';
        if ($q !== '') {
            $where[] = "(LOWER({$alias}.description) LIKE :q" . Db::ESCAPE . " OR LOWER({$alias}.action) LIKE :q2" . Db::ESCAPE . ' OR LOWER(p.display_name) LIKE :q3' . Db::ESCAPE . ')';
            $params['q'] = $params['q2'] = $params['q3'] = Db::like($q);
        }
        if ($from) {
            $where[] = "{$alias}.created_at >= :from";
            $params['from'] = $from . ' 00:00:00';
        }
        if ($to) {
            $where[] = "{$alias}.created_at <= :to";
            $params['to'] = $to . ' 23:59:59';
        }

        $result = $tab === 'admin'
            ? Db::paginate('a.action, a.object_type, a.object_id, a.description, a.ip, a.meta, a.created_at, p.display_name', 'FROM admin_actions a LEFT JOIN profiles p ON p.user_id = a.admin_id WHERE ' . implode(' AND ', $where), $params, $this->page($request), 40, 'a.created_at DESC, a.id DESC')
            : Db::paginate('g.action, g.object_type, g.object_id, g.description, g.ip, g.created_at, p.display_name, u.role', 'FROM activity_log g LEFT JOIN users u ON u.id = g.user_id LEFT JOIN profiles p ON p.user_id = g.user_id WHERE ' . implode(' AND ', $where), $params, $this->page($request), 40, 'g.created_at DESC, g.id DESC');

        return $this->render('admin/system/activity', ['title' => 'Atividade', 'tab' => $tab, 'q' => $q, 'result' => $result]);
    }

    public function notifications(Request $request): Response
    {
        return $this->render('admin/system/notifications', [
            'title' => 'Notificações',
            'recent' => Db::all(
                "SELECT description, meta, created_at FROM admin_actions WHERE action = 'notification.broadcast' ORDER BY created_at DESC, id DESC LIMIT 20"
            ),
        ]);
    }

    public function broadcast(Request $request): Response
    {
        Gate::master();
        $this->throttle('broadcast:' . $this->user()->id, 5, 3600);
        $data = [
            'audience' => (string) $request->input('audience', ''),
            'title' => trim((string) $request->input('title', '')),
            'body' => trim((string) $request->input('body', '')),
        ];
        if ($response = $this->invalid($data, [
            'audience' => 'required|in:creator,company,all',
            'title' => 'required|min:5|max:120',
            'body' => 'required|min:10|max:500',
        ], ['audience' => 'Público', 'title' => 'Título', 'body' => 'Mensagem'], '/admin/notificacoes')) {
            return $response;
        }
        $roles = $data['audience'] === 'all' ? "'creator', 'company'" : "'" . $data['audience'] . "'";
        $ids = array_map('intval', array_column(Db::all("SELECT id FROM users WHERE role IN ({$roles}) AND status = 'active' AND deleted_at IS NULL"), 'id'));
        Db::transaction(static function () use ($ids, $data): void {
            foreach ($ids as $id) {
                Db::insert('notifications', ['user_id' => $id, 'type' => 'broadcast', 'title' => $data['title'], 'body' => $data['body'], 'url' => null, 'created_at' => now()]);
            }
        });
        Audit::admin('notification.broadcast', 'notification', null, 'Enviou "' . $data['title'] . '" para ' . count($ids) . ' usuários', ['audience' => $data['audience']]);
        $this->success('Notificação enviada para ' . count($ids) . ' usuários.');

        return $this->redirect('/admin/notificacoes');
    }
}
