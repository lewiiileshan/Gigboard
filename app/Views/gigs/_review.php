<?php
// Shown on a completed gig to the client and the hired freelancer.
$mine     = null;
foreach ($reviews as $r) if ((int) $r['reviewer_id'] === (int) $user['id']) $mine = $r;
$otherName = $isOwner ? $hired['freelancer_name'] : $gig['client_name'];
?>
<h3 class="section-title">Reviews</h3>
<?php foreach ($reviews as $r): ?>
  <div class="card">
    <div class="row">
      <strong><?= (int) $r['reviewer_id'] === (int) $user['id'] ? 'Your review' : e($r['reviewer_name']) . '\'s review' ?></strong>
      <span class="rating" aria-label="<?= (int) $r['rating'] ?> out of 5"><?= icon('star') ?><?= (int) $r['rating'] ?>/5</span>
    </div>
    <?php if (!empty($r['comment'])): ?><p class="desc" style="margin-top:8px"><?= e($r['comment']) ?></p><?php endif; ?>
  </div>
<?php endforeach; ?>

<?php if (!$mine): ?>
  <form class="card" method="post" action="<?= url('/gigs/' . (int) $gig['id'] . '/review') ?>" novalidate data-validate>
    <?= csrf_field() ?>
    <h3 style="margin:0 0 4px">Rate <?= e($otherName) ?></h3>
    <p class="muted small" style="margin-top:0">Tell others how this gig went. You can review once.</p>
    <fieldset class="rate-pick">
      <legend>Rating</legend>
      <div class="rate-row">
      <?php for ($i = 5; $i >= 1; $i--): ?>
        <label><input type="radio" name="rating" value="<?= $i ?>" <?= $i === 5 ? 'required' : '' ?> <?= (string) old('rating') === (string) $i ? 'checked' : '' ?>><?= $i ?></label>
      <?php endfor; ?>
      </div>
    </fieldset>
    <p class="muted small" style="margin:6px 0 0">5 is excellent, 1 is poor.</p>
    <label for="comment">Comment (optional)</label>
    <textarea id="comment" name="comment" rows="3" maxlength="500" placeholder="What went well? What could be better?"><?= e(old('comment')) ?></textarea>
    <button class="btn block" type="submit" style="margin-top:14px">Save review</button>
  </form>
<?php else: ?>
  <p class="muted small">Thanks for reviewing <?= e($otherName) ?>.</p>
<?php endif; ?>
