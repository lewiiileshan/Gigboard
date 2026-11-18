<?php $skills = \App\Models\FreelancerProfile::skillList($profile['skills'] ?? ''); ?>
<div class="profile-head">
  <span class="avatar lg"><?= e(mb_strtoupper(mb_substr($f['name'], 0, 1))) ?></span>
  <div>
    <h2><?= e($f['name']) ?></h2>
    <span class="meta-label">Freelancer</span>
    <?php if ($rating['count']): ?><div class="rating" style="margin-top:6px"><?= icon('star') ?><strong><?= number_format($rating['avg'], 1) ?></strong> <span class="muted">(<?= (int) $rating['count'] ?> <?= $rating['count'] === 1 ? 'review' : 'reviews' ?>)</span></div><?php else: ?><div class="muted small" style="margin-top:6px">No reviews yet</div><?php endif; ?>
  </div>
</div>

<div class="card">
  <?php if (!empty($profile['headline'])): ?><p style="margin:0 0 10px;font-weight:600"><?= e($profile['headline']) ?></p><?php endif; ?>
  <?php if (!empty($profile['bio'])): ?><p class="desc"><?= e($profile['bio']) ?></p>
  <?php else: ?><p class="muted">This freelancer has not written a bio yet.</p><?php endif; ?>

  <?php if ($skills): ?>
    <div class="chips tight" style="justify-content:flex-start;margin-top:12px">
      <?php foreach ($skills as $s): ?><span class="chip"><?= e($s) ?></span><?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="info-row"><span class="muted">Town</span><strong><?= !empty($profile['town']) ? e($profile['town']) : 'Not added' ?></strong></div>
  <div class="info-row"><span class="muted">Hourly rate</span><strong><?= isset($profile['hourly_rate']) && $profile['hourly_rate'] !== null ? e(kes($profile['hourly_rate'])) : 'Not added' ?></strong></div>
  <div class="info-row"><span class="muted">Phone</span><strong><?= $f['phone'] ? e($f['phone']) : 'Not added' ?></strong></div>
  <div class="info-row"><span class="muted">Member since</span><strong><?= e(date('d M Y', strtotime($f['created_at']))) ?></strong></div>
  <?php if (!empty($profile['portfolio_url'])): ?>
    <div class="info-row"><span class="muted">Portfolio</span>
      <a href="<?= e($profile['portfolio_url']) ?>" target="_blank" rel="noopener noreferrer nofollow"><strong>Open link</strong></a></div>
  <?php endif; ?>
</div>

<?php if ($reviews): ?>
  <h3 class="section-title">Reviews</h3>
  <?php foreach ($reviews as $r): ?>
    <div class="card">
      <div class="row"><strong><?= e($r['reviewer_name']) ?></strong>
        <span class="rating" aria-label="<?= (int) $r['rating'] ?> out of 5"><?= icon('star') ?><?= (int) $r['rating'] ?>/5</span></div>
      <p class="muted small" style="margin:2px 0 0">For: <?= e($r['gig_title']) ?></p>
      <?php if (!empty($r['comment'])): ?><p class="desc" style="margin-top:8px"><?= e($r['comment']) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php if ($isSelf): ?><a class="btn block" href="<?= url('/account/edit') ?>">Edit profile</a><?php endif; ?>
