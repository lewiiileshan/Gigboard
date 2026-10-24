<a class="card gig-card" href="<?= url('/gigs/' . (int) $g['id']) ?>">
  <div class="row">
    <span class="tag"><?= e($g['category_name']) ?></span>
    <span class="price"><?= e(kes($g['budget'])) ?></span>
  </div>
  <h3><?= e($g['title']) ?></h3>
  <p class="muted clamp"><?= e($g['description']) ?></p>
  <div class="row meta">
    <span>📍 <?= e($g['location']) ?></span>
    <span><?= e($g['client_name']) ?> · <?= e(time_ago($g['created_at'])) ?></span>
  </div>
</a>
