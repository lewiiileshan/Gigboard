<div class="crumbs"><a href="<?= url('/gigs') ?>">Gigs</a> / <?= e($gig['category_name']) ?></div>
<div class="gig-layout">
<div>
<div class="card">
  <div class="row">
    <span class="meta-label"><?= e($gig['category_name']) ?></span>
    <?= status_tag($gig['status']) ?>
  </div>
  <h2 style="margin:10px 0 4px;font-size:24px"><?= e($gig['title']) ?></h2>
  <p class="muted small">Posted <?= e(time_ago($gig['created_at'])) ?></p>

  <?php if (!empty($gig['removal_reason']) && ($isOwner || $isAdmin)): ?>
    <p class="card" style="margin:14px 0 0;border-left:4px solid var(--danger)"><strong>Removed by an administrator.</strong> Reason: <?= e($gig['removal_reason']) ?></p>
  <?php endif; ?>

  <h3 class="sec-h">Description</h3>
  <p class="desc"><?= e($gig['description']) ?></p>

  <?php if (!empty($gig['requirements'])): ?>
    <h3 class="sec-h">Requirements</h3>
    <p class="desc"><?= e($gig['requirements']) ?></p>
  <?php endif; ?>
</div>

<?php if ($isOwner && $gig['status'] === 'open'): ?>
  <?php require __DIR__ . '/_applicants.php'; ?>
<?php elseif (!$isOwner && $user['role'] === 'freelancer' && ($gig['status'] === 'open' || $myApplication)): ?>
  <?php require __DIR__ . '/_apply.php'; ?>
<?php endif; ?>

<?php if ($hired && ($isOwner || $isHired)): ?>
  <?php require __DIR__ . '/_delivery.php'; ?>
  <?php if ($payment) require __DIR__ . '/_payment.php'; ?>
  <?php if ($gig['status'] === 'completed') require __DIR__ . '/_review.php'; ?>
<?php endif; ?>
</div>

<aside class="gig-side">
  <div class="card summary">
    <dl>
      <div class="line"><dt>Budget</dt><dd class="price"><?= e(kes($gig['budget'])) ?></dd></div>
      <div class="line"><dt>Location</dt><dd><?= e($gig['location']) ?></dd></div>
      <div class="line"><dt>Deadline</dt><dd><?= $gig['deadline'] ? e(date('d M Y', strtotime($gig['deadline']))) : 'Flexible' ?></dd></div>
      <div class="line"><dt>Status</dt><dd><?= e(ucfirst(str_replace('_', ' ', $gig['status']))) ?></dd></div>
      <div class="line"><dt>Proposals</dt><dd><?= (int) ($gig['applicants'] ?? 0) ?></dd></div>
    </dl>
    <?php if ($isOwner && $gig['status'] === 'open'): ?>
      <div class="actions" style="margin-top:12px">
        <a class="btn" href="<?= url('/gigs/' . (int) $gig['id'] . '/edit') ?>">Edit gig</a>
        <form method="post" action="<?= url('/gigs/' . (int) $gig['id'] . '/cancel') ?>"
              data-confirm-title="Cancel &quot;<?= e($gig['title']) ?>&quot;?" data-confirm-text="The gig leaves the browse list and applicants are told it is closed. This cannot be undone." data-confirm-action="Cancel gig" data-confirm-back="Keep gig">
          <?= csrf_field() ?>
          <button class="btn danger" type="submit">Cancel gig</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
  <div class="card">
    <div class="client-box">
      <span class="avatar"><?= e(mb_strtoupper(mb_substr($gig['client_name'], 0, 1))) ?></span>
      <div><div class="muted small">Posted by</div><strong><?= e($gig['client_name']) ?></strong>
        <?php if ($clientRating['count']): ?><div class="rating small"><?= icon('star') ?><?= number_format($clientRating['avg'], 1) ?> <span class="muted">(<?= (int) $clientRating['count'] ?> <?= $clientRating['count'] === 1 ? 'review' : 'reviews' ?>)</span></div><?php endif; ?></div>
    </div>
  </div>
</aside>
</div>
