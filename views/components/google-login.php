<?php if (\App\Services\SettingService::googleEnabled()): ?>
    <a class="btn btn-google" href="<?= e(url('/entrar/google' . (!empty($intent) ? '?intent=' . rawurlencode((string) $intent) : ''))) ?>">
        Continuar com Google
    </a>
    <p class="auth-or"><span>ou entre com e-mail</span></p>
<?php endif; ?>
