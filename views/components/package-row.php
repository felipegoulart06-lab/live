<?php $r = static fn (string $k): string => (string) ($row[$k] ?? ''); ?>
<div class="repeat-row" data-repeat-row>
    <button class="btn btn-link row-remove" type="button" data-repeat-remove>Remover</button>
    <input type="hidden" name="id[]" value="<?= e($r('id')) ?>">
    <div class="repeat-grid">
        <label class="field"><span>Horas</span><input type="number" name="hours[]" min="<?= (int) $limits['min_hours'] ?>" max="<?= (int) $limits['max_hours'] ?>" step="1" value="<?= e($r('hours')) ?>" required></label>
        <label class="field"><span>Preço (R$)</span><input name="price[]" inputmode="decimal" placeholder="0,00" value="<?= e($r('price')) ?>" required></label>
        <label class="field"><span>Entrega (dias)</span><input type="number" name="delivery_days[]" min="1" max="90" value="<?= e($r('delivery_days')) ?>" required></label>
        <label class="field"><span>Revisões</span><input type="number" name="revisions[]" min="0" max="10" value="<?= e($r('revisions')) ?>" placeholder="0"></label>
    </div>
    <label class="field"><span>O que está incluído <small>(opcional)</small></span><input name="description[]" maxlength="500" value="<?= e($r('description')) ?>" placeholder="Ex.: 2 horas de gravação, material bruto organizado e 1 versão editada"></label>
</div>
