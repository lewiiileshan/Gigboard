<div class="card">
  <div class="row">
    <span class="tag"><?= e($gig['category_name']) ?></span>
    <?= status_tag($gig['status']) ?>
  </div>
  <h2 style="margin:10px 0 4px"><?= e($gig['title']) ?></h2>
  <p class="muted small">Posted by <?= e($gig['client_name']) ?> · <?= e(time_ago($gig['created_at'])) ?></p>

  <dl class="detail-grid">
    <div><dt>Budget</dt><dd class="price"><?= e(kes($gig['budget'])) ?></dd></div>
    <div><dt>Location</dt><dd><?= e($gig['location']) ?></dd></div>
    <div><dt>Deadline</dt><dd><?= $gig['deadline'] ? e(date('d M Y', strtotime($gig['deadline']))) : 'Flexible' ?></dd></div>
    <div><dt>Status</dt><dd><?= e(ucfirst(str_replace('_', ' ', $gig['status']))) ?></dd></div>
  </dl>

  <h3>Description</h3>
  <p class="desc"><?= e($gig['description']) ?></p>

  <?php if (!empty($gig['requirements'])): ?>
    <h3 style="margin-top:18px">Requirements</h3>
    <p class="desc"><?= e($gig['requirements']) ?></p>
  <?php endif; ?>

  <?php if ($isOwner && $gig['status'] === 'open'): ?>
    <div class="row" style="justify-content:flex-start;gap:10px;margin-top:16px">
      <a class="btn" href="<?= url('/gigs/' . (int) $gig['id'] . '/edit') ?>">Edit gig</a>
      <form method="post" action="<?= url('/gigs/' . (int) $gig['id'] . '/cancel') ?>"
            onsubmit="return confirm('Cancel this gig? This cannot be undone.')">
        <?= csrf_field() ?>
        <button class="btn danger" type="submit">Cancel gig</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<?php if ($isOwner && $gig['status'] === 'open'): ?>
  <?php require __DIR__ . '/_applicants.php'; ?>
<?php elseif (!$isOwner && $user['role'] === 'freelancer' && ($gig['status'] === 'open' || $myApplication)): ?>
  <?php require __DIR__ . '/_apply.php'; ?>
<?php endif; ?>

<?php if ($hired && ($isOwner || $isHired)): ?>
  <?php require __DIR__ . '/_delivery.php'; ?>
<?php endif; ?>
