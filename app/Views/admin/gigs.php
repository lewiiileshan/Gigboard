<div class="page-head"><h2>Gigs</h2></div>

<form class="card" method="get" action="<?= url('/admin/gigs') ?>">
  <div class="form-row">
    <div><label for="q">Title or client</label><input id="q" name="q" value="<?= e($q) ?>"></div>
    <div><label for="status">Status</label>
      <select id="status" name="status">
        <option value="">All statuses</option>
        <?php foreach (['open' => 'Open', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $k => $label): ?>
          <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select></div>
  </div>
  <div class="actions" style="margin-top:14px"><button class="btn" type="submit">Search</button><a class="btn ghost" href="<?= url('/admin/gigs') ?>">Clear</a></div>
</form>

<p class="muted small" style="margin:10px 2px"><?= count($gigs) ?> <?= count($gigs) === 1 ? 'gig' : 'gigs' ?> shown</p>
<?php if (!$gigs): ?>
  <div class="empty">No gigs match.</div>
<?php else: ?>
  <div class="stack">
  <?php foreach ($gigs as $g): ?>
    <div class="card">
      <div class="row">
        <div style="min-width:0">
          <a href="<?= url('/gigs/' . (int) $g['id']) ?>"><strong><?= e($g['title']) ?></strong></a>
          <div class="muted small">by <?= e($g['client_name']) ?> · <?= e(kes($g['budget'])) ?> · <?= e(time_ago($g['created_at'])) ?></div>
        </div>
        <?= status_tag($g['status']) ?>
      </div>
      <?php if (!empty($g['removal_reason'])): ?>
        <p class="muted small" style="margin-top:8px">Removed: <?= e($g['removal_reason']) ?></p>
      <?php elseif ($g['status'] === 'open'): ?>
        <form method="post" action="<?= url('/admin/gigs/' . (int) $g['id'] . '/remove') ?>" style="margin-top:12px"
              data-validate data-confirm-title="Remove &quot;<?= e($g['title']) ?>&quot;?" data-confirm-text="The client will see the reason you typed." data-confirm-action="Remove gig">
          <?= csrf_field() ?>
          <label for="reason-<?= (int) $g['id'] ?>" style="margin-top:0">Reason for removing (shown to the client)</label>
          <div class="row" style="align-items:stretch">
            <input id="reason-<?= (int) $g['id'] ?>" name="reason" maxlength="255" minlength="5" required>
            <button class="btn danger" type="submit">Remove</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
