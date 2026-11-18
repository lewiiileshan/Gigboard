<?php /** @var array $files */ ?>
<?php if ($files): ?>
  <div class="quals">
    <div class="muted small" style="margin-bottom:6px">Qualification files</div>
    <?php foreach ($files as $f): ?>
      <a class="qual-row" href="<?= url('/qualifications/' . (int) $f['id'] . '/download') ?>">
        <span class="qual-ico"><?= icon('briefcase') ?></span>
        <span class="qual-text"><strong><?= e($f['title']) ?></strong>
          <span class="muted small"><?= e($f['original_name']) ?> · <?= e(format_bytes((int) $f['size_bytes'])) ?></span></span>
        <?= icon('chevron') ?>
      </a>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <p class="muted small" style="margin:8px 0 0">No qualification files attached.</p>
<?php endif; ?>
