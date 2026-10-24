<div class="page-head row">
  <h2 style="margin:0">My gigs</h2>
  <a class="btn sm" href="<?= url('/gigs/create') ?>">+ Post a gig</a>
</div>

<?php if (!$gigs): ?>
  <div class="empty"><span class="ico">📝</span>You have not posted any gigs yet.</div>
<?php else: ?>
  <div class="stack">
    <?php foreach ($gigs as $g): ?>
      <a class="card gig-card" href="<?= url('/gigs/' . (int) $g['id']) ?>">
        <div class="row">
          <span class="tag"><?= e($g['category_name']) ?></span>
          <?= status_tag($g['status']) ?>
        </div>
        <h3><?= e($g['title']) ?></h3>
        <div class="row meta">
          <span class="price"><?= e(kes($g['budget'])) ?></span>
          <span><?= e(time_ago($g['created_at'])) ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
