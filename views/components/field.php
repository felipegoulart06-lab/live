<?php
/**
 * @var string $name
 * @var string $label
 * @var string|null $type text|email|password|number|date|time|url|textarea|select
 * @var mixed $value current value (old input wins)
 * @var array<string, string>|null $options for select
 * @var string|null $hint
 * @var string|null $attrs extra raw attributes (developer-provided only)
 */
$type ??= 'text';
$id = 'f-' . preg_replace('/[^a-z0-9_-]/i', '-', $name);
$current = $type === 'password' ? '' : (string) old($name, $value ?? '');
$message = error_field($name);
$attrs ??= '';
?>
<label class="field<?= $message ? ' has-error' : '' ?><?= !empty($span) ? ' span-2' : '' ?>" for="<?= e($id) ?>">
    <span><?= e($label) ?><?php if (!empty($optional)): ?> <small>(opcional)</small><?php endif; ?></span>
    <?php if ($type === 'textarea'): ?>
        <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" <?= $attrs ?> <?= $message ? 'aria-invalid="true" aria-describedby="' . e($id) . '-err"' : '' ?>><?= e($current) ?></textarea>
    <?php elseif ($type === 'select'): ?>
        <select id="<?= e($id) ?>" name="<?= e($name) ?>" <?= $attrs ?> <?= $message ? 'aria-invalid="true" aria-describedby="' . e($id) . '-err"' : '' ?>>
            <?php foreach ($options ?? [] as $optValue => $optLabel): ?>
                <option value="<?= e($optValue) ?>" <?= (string) $optValue === $current ? 'selected' : '' ?>><?= e($optLabel) ?></option>
            <?php endforeach; ?>
        </select>
    <?php else: ?>
        <input id="<?= e($id) ?>" type="<?= e($type) ?>" name="<?= e($name) ?>" value="<?= e($current) ?>" <?= $attrs ?> <?= $message ? 'aria-invalid="true" aria-describedby="' . e($id) . '-err"' : '' ?>>
    <?php endif; ?>
    <?php if (!empty($hint)): ?><small><?= e($hint) ?></small><?php endif; ?>
    <?php if ($message): ?><span class="field-err" id="<?= e($id) ?>-err"><?= e($message) ?></span><?php endif; ?>
</label>
