<h3 class="section-title">Work delivery</h3>

<div class="card">
  <div class="row">
    <div>
      <div class="muted small">Hired freelancer</div>
      <strong><?= e($hired['freelancer_name']) ?></strong>
    </div>
    <span class="price"><?= e(kes($hired['proposed_price'])) ?></span>
  </div>
  <p class="muted small" style="margin:10px 0 0">🔒 Delivered files are private. Only <?= $isOwner ? 'you and ' . e($hired['freelancer_name']) : 'the client and you' ?> can download them.</p>
</div>

<?php if ($isHired && $gig['status'] === 'in_progress'): ?>
  <form class="card" method="post" action="<?= url('/gigs/' . (int) $gig['id'] . '/submit') ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <h3 style="margin:0 0 4px">Upload your finished work</h3>
    <p class="muted small" style="margin-top:0">Allowed: <?= e(\App\Core\FileStore::allowedList()) ?> · up to 10 MB. For many files, upload one ZIP.</p>

    <label for="work">File</label>
    <input id="work" name="work" type="file">

    <label for="note">Message to the client (optional)</label>
    <textarea id="note" name="note" rows="3" maxlength="1000" placeholder="Anything the client should know about this delivery"></textarea>

    <button class="btn block" type="submit" style="margin-top:14px">Upload work</button>
  </form>
<?php endif; ?>

<?php if (!$submissions): ?>
  <div class="empty" style="padding:24px 12px"><span class="ico">📂</span>No work uploaded yet.</div>
<?php else: ?>
  <div class="stack">
    <?php foreach ($submissions as $s): ?>
      <div class="card">
        <div class="row">
          <strong class="file-name"><?= e($s['original_name']) ?></strong>
          <span class="tag <?= $s['status'] === 'approved' ? 'ok' : ($s['status'] === 'changes_requested' ? 'danger' : 'warn') ?>">
            <?= e(['submitted' => 'Awaiting review', 'approved' => 'Approved', 'changes_requested' => 'Changes requested'][$s['status']] ?? $s['status']) ?>
          </span>
        </div>
        <p class="muted small" style="margin:2px 0 8px"><?= e(format_bytes((int) $s['size_bytes'])) ?> · <?= e(time_ago($s['created_at'])) ?></p>
        <?php if ($s['note']): ?><p class="desc"><?= e($s['note']) ?></p><?php endif; ?>
        <?php if ($s['client_feedback']): ?><p class="desc" style="color:var(--danger)"><strong>Client feedback:</strong> <?= e($s['client_feedback']) ?></p><?php endif; ?>

        <a class="btn ghost block" style="margin-top:12px" href="<?= url('/submissions/' . (int) $s['id'] . '/download') ?>">Download file</a>

        <?php if ($isOwner && $s['status'] === 'submitted' && $gig['status'] === 'in_progress'): ?>
          <form method="post" action="<?= url('/submissions/' . (int) $s['id'] . '/approve') ?>" style="margin-top:10px"
                onsubmit="return confirm('Approve this work? The gig will be marked completed.')">
            <?= csrf_field() ?>
            <button class="btn block" type="submit">Approve work</button>
          </form>
          <form method="post" action="<?= url('/submissions/' . (int) $s['id'] . '/changes') ?>" style="margin-top:10px">
            <?= csrf_field() ?>
            <label for="fb<?= (int) $s['id'] ?>">What should change?</label>
            <textarea id="fb<?= (int) $s['id'] ?>" name="feedback" rows="3" maxlength="1000" placeholder="Be specific so the freelancer can fix it"></textarea>
            <button class="btn danger block" type="submit" style="margin-top:10px">Request changes</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
