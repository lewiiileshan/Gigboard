<?php if ($myApplication): ?>
  <div class="card">
    <div class="row"><h3 style="margin:0">Your application</h3>
      <span class="tag <?= $myApplication['status'] === 'accepted' ? 'ok' : ($myApplication['status'] === 'rejected' ? 'danger' : 'warn') ?>"><?= e(ucfirst($myApplication['status'])) ?></span></div>
    <p class="muted small">Your price: <strong><?= e(kes($myApplication['proposed_price'])) ?></strong> · sent <?= e(time_ago($myApplication['created_at'])) ?></p>
    <p class="desc"><?= e($myApplication['proposal']) ?></p>
    <?php $files = $appFiles[(int) $myApplication['id']] ?? []; require __DIR__ . '/_files.php'; ?>
  </div>
<?php else: ?>
  <?php $flashShown = true; // errors show under the form ?>
  <form class="card" method="post" action="<?= url('/gigs/' . (int) $gig['id'] . '/apply') ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <h3 style="margin:0 0 4px">Apply for this gig</h3>
    <p class="muted small" style="margin-top:0">Tell the client why you are the right person and what you will charge.</p>

    <label for="proposal">Your proposal</label>
    <textarea id="proposal" name="proposal" rows="5" maxlength="2000" placeholder="How will you do the work, and what have you done like it before?"><?= e(old('proposal')) ?></textarea>

    <label for="price">Your price (KES)</label>
    <input id="price" name="price" inputmode="numeric" value="<?= e(old('price', (string) (int) $gig['budget'])) ?>" placeholder="e.g. 3000">

    <h3 style="margin:18px 0 4px">Your qualifications</h3>
    <p class="muted small" style="margin-top:0">
      <?= !empty($gig['requirements']) ? 'The client asked for: <strong>' . e($gig['requirements']) . '</strong><br>' : '' ?>
      Add up to 3 files that prove it (certificate, CV, portfolio). Only the client can open them.
      Allowed: <?= e(\App\Core\FileStore::allowedList()) ?> · 10 MB each.
    </p>
    <?php for ($i = 1; $i <= \App\Models\ApplicationFile::MAX_PER_APPLICATION; $i++): ?>
      <div class="qual-slot">
        <label for="qt<?= $i ?>">Title <?= $i ?><?= $i > 1 ? ' (optional)' : '' ?></label>
        <input id="qt<?= $i ?>" name="qual_title_<?= $i ?>" maxlength="80" placeholder="e.g. Diploma in Graphic Design">
        <label for="qf<?= $i ?>">File <?= $i ?></label>
        <input id="qf<?= $i ?>" name="qual_file_<?= $i ?>" type="file">
      </div>
    <?php endfor; ?>

    <div class="error"><?= $flash && $flash['type'] === 'error' ? e($flash['message']) : '' ?></div>
    <button class="btn block" type="submit">Send application</button>
  </form>
<?php endif; ?>
