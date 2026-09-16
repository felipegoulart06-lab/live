<?php if (!empty($success)): ?>
    <div class="alert alert-ok" role="status"><?= e($success) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-err" role="alert"><?= e($error) ?></div>
<?php endif; ?>
