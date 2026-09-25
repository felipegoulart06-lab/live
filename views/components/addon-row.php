<?php $r = static fn (string $k): string => (string) ($row[$k] ?? ''); ?>
<div class="repeat-row" data-repeat-row>
    <button class="btn btn-link row-remove" type="button" data-repeat-remove>Remover</button>
    <input type="hidden" name="id[]" value="<?= e($r('id')) ?>">
    <div class="repeat-grid-addon">
        <label class="field"><span>Nome</span><input name="name[]" minlength="3" maxlength="80" value="<?= e($r('name')) ?>" required placeholder="Ex.: Roteiro"></label>
        <label class="field"><span>Preço (R$)</span><input name="price[]" inputmode="decimal" placeholder="0,00" value="<?= e($r('price')) ?>" required></label>
        <label class="field"><span>Dias extras</span><input type="number" name="extra_days[]" min="0" max="30" value="<?= e($r('extra_days')) ?>" placeholder="0"></label>
    </div>
    <label class="field"><span>Descrição <small>(opcional)</small></span><input name="description[]" maxlength="300" value="<?= e($r('description')) ?>"></label>
</div>
