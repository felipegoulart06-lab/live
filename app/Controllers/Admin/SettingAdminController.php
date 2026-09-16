<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Services\GoogleAuthService;
use App\Services\SettingService;

final class SettingAdminController extends AdminController
{
    public function __construct(private readonly GoogleAuthService $google = new GoogleAuthService())
    {
    }

    public function edit(Request $request): Response
    {
        if ($denied = $this->canOrDeny('settings.manage')) {
            return $denied;
        }

        $this->ensureKeys();

        return $this->panel('admin/settings/form', [
            'title' => 'Configurações',
            'googleRedirect' => $this->google->redirectUri(),
            'googleReady' => $this->google->enabled(),
        ]);
    }

    public function update(Request $request): Response
    {
        if ($denied = $this->canOrDeny('settings.manage')) {
            return $denied;
        }

        $pairs = [
            'platform_name' => [trim((string) $request->input('platform_name', 'CinquentaConto')), 'general'],
            'tagline' => [trim((string) $request->input('tagline', '')), 'general'],
            'support_email' => [trim((string) $request->input('support_email', '')), 'general'],
            'support_phone' => [trim((string) $request->input('support_phone', '')), 'general'],
            'whatsapp' => [trim((string) $request->input('whatsapp', '')), 'general'],
            'maintenance_mode' => [empty($request->input('maintenance_mode')) ? '0' : '1', 'general'],
            'registrations_open' => [empty($request->input('registrations_open')) ? '0' : '1', 'general'],
            'meta_title' => [trim((string) $request->input('meta_title', '')), 'seo'],
            'meta_description' => [trim((string) $request->input('meta_description', '')), 'seo'],
            'commission_percent' => [trim((string) $request->input('commission_percent', '10')), 'finance'],
            'fixed_fee_cents' => [trim((string) $request->input('fixed_fee_cents', '0')), 'finance'],
            'withdraw_fee_cents' => [trim((string) $request->input('withdraw_fee_cents', '0')), 'finance'],
            'min_withdraw_cents' => [trim((string) $request->input('min_withdraw_cents', '5000')), 'finance'],
            'social_instagram' => [trim((string) $request->input('social_instagram', '')), 'social'],
            'social_linkedin' => [trim((string) $request->input('social_linkedin', '')), 'social'],
            'ga_id' => [trim((string) $request->input('ga_id', '')), 'integrations'],
            'gtm_id' => [trim((string) $request->input('gtm_id', '')), 'integrations'],
            'meta_pixel' => [trim((string) $request->input('meta_pixel', '')), 'integrations'],
            'google_oauth_enabled' => [empty($request->input('google_oauth_enabled')) ? '0' : '1', 'auth'],
            'google_client_id' => [trim((string) $request->input('google_client_id', '')), 'auth'],
        ];

        $secret = trim((string) $request->input('google_client_secret', ''));
        if ($secret !== '') {
            $pairs['google_client_secret'] = [$secret, 'auth'];
        }

        SettingService::putMany($pairs);
        $this->audit('settings.update', 'settings', null);
        $this->withSuccess('Configurações salvas.');

        return $this->redirect('/admin/configuracoes');
    }

    private function ensureKeys(): void
    {
        $defaults = [
            'google_oauth_enabled' => ['0', 'auth'],
            'google_client_id' => ['', 'auth'],
            'google_client_secret' => ['', 'auth'],
        ];
        $missing = [];
        foreach ($defaults as $key => $pair) {
            if (setting($key, null) === null) {
                $missing[$key] = $pair;
            }
        }
        if ($missing !== []) {
            SettingService::putMany($missing);
        }
    }
}
