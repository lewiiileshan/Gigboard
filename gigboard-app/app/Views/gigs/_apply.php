<?php if ($myApplication): ?>
  <div class="card">
    <div class="row"><h3 style="margin:0">Your application</h3>
      <span class="tag <?= $myApplication['status'] === 'accepted' ? 'ok' : ($myApplication['status'] === 'rejected' ? 'danger' : 'warn') ?>"><?= e(ucfirst($myApplication['status'])) ?></span></div>
    <p class="muted small">Your price: <strong><?= e(kes($myApplication['proposed_price'])) ?></strong> · sent <?= e(time_ago($myApplication['created_at'])) ?></p>
    <p class="desc"><?= e($myApplication['proposal']) ?></p>
  </div>
<?php else: ?>
  <?php $flashShown = true; // errors show under the form ?>
  <form class="card" method="post" action="<?= url('/gigs/' . (int) $gig['id'] . '/apply') ?>" novalidate>
    <?= csrf_field() ?>
    <h3 style="margin:0 0 4px">Apply for this gig</h3>
    <p class="muted small" style="margin-top:0">Tell the client why you are the right person and what you will charge.</p>

    <label for="proposal">Your proposal</label>
    <textarea id="proposal" name="proposal" rows="5" maxlength="2000" placeholder="How will you do the work, and what have you done like it before?"><?= e(old('proposal')) ?></textarea>

    <label for="price">Your price (KES)</label>
    <input id="price" name="price" inputmode="numeric" value="<?= e(old('price', (string) (int) $gig['budget'])) ?>" placeholder="e.g. 3000">

    <div class="error"><?= $flash && $flash['type'] === 'error' ? e($flash['message']) : '' ?></div>
    <button class="btn block" type="submit">Send application</button>
  </form>
<?php endif; ?>
