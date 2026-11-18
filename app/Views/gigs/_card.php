<a class="card gig-row" href="<?= url('/gigs/' . (int) $g['id']) ?>">
  <div class="top">
    <div style="min-width:0">
      <span class="meta-label"><?= e($g['category_name']) ?></span>
      <h3><?= e($g['title']) ?></h3>
    </div>
    <span class="price"><?= e(kes($g['budget'])) ?></span>
  </div>
  <p class="muted clamp" style="margin-top:6px"><?= e($g['description']) ?></p>
  <div class="meta">
    <span><?= icon('pin') ?><?= e($g['location']) ?></span>
    <span><?= icon('clock') ?><?= e(time_ago($g['created_at'])) ?></span>
    <span><?= icon('users') ?><?= (int) ($g['applicants'] ?? 0) ?> <?= (int) ($g['applicants'] ?? 0) === 1 ? 'proposal' : 'proposals' ?></span>
    <span>by <?= e($g['client_name']) ?></span>
  </div>
</a>
