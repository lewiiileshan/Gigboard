<div class="page-head"><h2 style="margin:0">My applications</h2></div>

<?php if (!$applications): ?>
  <div class="empty"><span class="ico">📨</span>You have not applied for any gigs yet.<br>
    <a class="btn sm" style="margin-top:12px" href="<?= url('/gigs') ?>">Browse gigs</a></div>
<?php else: ?>
  <div class="stack">
    <?php foreach ($applications as $a): ?>
      <?php $link = $a['status'] === 'rejected' && $a['gig_status'] !== 'open' ? null : url('/gigs/' . (int) $a['gig_id']); ?>
      <<?= $link ? 'a href="' . e($link) . '"' : 'div' ?> class="card gig-card">
        <div class="row">
          <span class="muted small"><?= e($a['client_name']) ?></span>
          <span class="tag <?= $a['status'] === 'accepted' ? 'ok' : ($a['status'] === 'rejected' ? 'danger' : 'warn') ?>"><?= e(ucfirst($a['status'])) ?></span>
        </div>
        <h3><?= e($a['title']) ?></h3>
        <div class="row meta">
          <span class="price"><?= e(kes($a['proposed_price'])) ?></span>
          <span><?= e(time_ago($a['created_at'])) ?></span>
        </div>
      </<?= $link ? 'a' : 'div' ?>>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
