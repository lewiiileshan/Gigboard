<div class="page-head"><h2>Admin overview</h2><p class="muted">Platform summary</p></div>

<div class="stats">
  <div class="card stat"><div class="num"><?= (int) $stats['clients'] ?></div><div class="lbl">Clients</div></div>
  <div class="card stat"><div class="num"><?= (int) $stats['freelancers'] ?></div><div class="lbl">Freelancers</div></div>
  <div class="card stat"><div class="num"><?= (int) $gigs['open'] ?></div><div class="lbl">Open gigs</div></div>
  <div class="card stat"><div class="num"><?= (int) $gigs['completed'] ?></div><div class="lbl">Completed gigs</div></div>
</div>
<div class="stats">
  <div class="card stat"><div class="num"><?= (int) $gigs['in_progress'] ?></div><div class="lbl">Gigs in progress</div></div>
  <div class="card stat"><div class="num"><?= (int) $stats['applications'] ?></div><div class="lbl">Applications</div></div>
  <div class="card stat"><div class="num"><?= (int) $stats['reviews'] ?></div><div class="lbl">Reviews</div></div>
  <div class="card stat"><div class="num"><?= number_format($stats['confirmed']) ?></div><div class="lbl">KES confirmed paid</div></div>
</div>

<?php if ($stats['suspended'] || $stats['disputed']): ?>
  <h3 class="section-title">Needs attention</h3>
  <div class="card feed">
    <?php if ($stats['disputed']): ?><div class="line"><span><?= (int) $stats['disputed'] ?> disputed <?= $stats['disputed'] === 1 ? 'payment' : 'payments' ?></span><span class="muted small">Between client and freelancer</span></div><?php endif; ?>
    <?php if ($stats['suspended']): ?><div class="line"><span><?= (int) $stats['suspended'] ?> suspended <?= $stats['suspended'] === 1 ? 'account' : 'accounts' ?></span><a class="btn sm ghost" href="<?= url('/admin/users') ?>">Review</a></div><?php endif; ?>
  </div>
<?php endif; ?>

<h3 class="section-title">Recent admin actions</h3>
<?php if (!$log): ?>
  <div class="empty">No admin actions yet.</div>
<?php else: ?>
  <div class="card feed">
    <?php foreach ($log as $a): ?>
      <div class="line">
        <?php $what = ['suspend_user' => 'suspended user', 'restore_user' => 'restored user', 'remove_gig' => 'removed gig'][$a['action']] ?? $a['action']; ?>
        <span><strong><?= e($a['admin_name']) ?></strong> <?= e($what . ' #' . (int) $a['target_id']) ?><?= $a['detail'] ? ' <span class="muted">(' . e($a['detail']) . ')</span>' : '' ?></span>
        <span class="muted small"><?= e(time_ago($a['created_at'])) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
