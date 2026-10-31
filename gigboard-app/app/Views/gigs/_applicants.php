<h3 class="section-title">Applicants (<?= count($applications) ?>)</h3>
<?php if (!$applications): ?>
  <div class="empty" style="padding:24px 12px"><span class="ico">🕐</span>No applications yet. Freelancers will appear here.</div>
<?php else: ?>
  <div class="stack">
    <?php foreach ($applications as $a): ?>
      <div class="card">
        <div class="row">
          <strong><?= e($a['freelancer_name']) ?></strong>
          <span class="price"><?= e(kes($a['proposed_price'])) ?></span>
        </div>
        <p class="muted small" style="margin:2px 0 8px"><?= e(time_ago($a['created_at'])) ?></p>
        <p class="desc"><?= e($a['proposal']) ?></p>
        <?php if ($a['status'] === 'pending'): ?>
          <form method="post" action="<?= url('/applications/' . (int) $a['id'] . '/hire') ?>"
                onsubmit="return confirm('Hire <?= e(addslashes($a['freelancer_name'])) ?> for this gig? Other applicants will be declined.')">
            <?= csrf_field() ?>
            <button class="btn block" type="submit">Hire <?= e($a['freelancer_name']) ?></button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
