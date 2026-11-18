<?php $isClient = $user['role'] === 'client'; ?>
<div class="page-head"><h2 style="margin:0">Payments</h2></div>

<div class="stats">
  <div class="card stat"><div class="num"><?= e(number_format($sum['in_progress'])) ?></div><div class="lbl">In progress (KES)</div></div>
  <div class="card stat"><div class="num"><?= e(number_format($sum['due'])) ?></div><div class="lbl"><?= $isClient ? 'To pay' : 'Awaiting payment' ?> (KES)</div></div>
  <div class="card stat"><div class="num"><?= e(number_format($sum['paid'])) ?></div><div class="lbl">Paid, unconfirmed (KES)</div></div>
  <div class="card stat"><div class="num"><?= e(number_format($sum['confirmed'])) ?></div><div class="lbl">Confirmed (KES)</div></div>
</div>
<p class="muted small" style="margin:12px 2px">GigBoard only tracks payments. It does not move money.</p>

<?php if (!$rows): ?>
  <div class="empty"><span class="ico"><?= icon('wallet') ?></span>No payments yet.<br>
    <?= $isClient ? 'A payment appears here when you hire someone.' : 'A payment appears here when a client hires you.' ?></div>
<?php else: ?>
  <div class="stack">
    <?php foreach ($rows as $r): ?>
      <?php
        $cls = ['pending' => 'warn', 'paid' => 'warn', 'confirmed' => 'ok', 'disputed' => 'danger'][$r['status']] ?? '';
        $txt = ['pending' => $r['gig_status'] === 'completed' ? 'Not paid yet' : 'In progress', 'paid' => 'Awaiting confirmation', 'confirmed' => 'Confirmed', 'disputed' => 'Not received'][$r['status']] ?? $r['status'];
      ?>
      <a class="card gig-card" href="<?= url('/gigs/' . (int) $r['gig_id']) ?>">
        <div class="row">
          <span class="muted small"><?= $isClient ? 'To ' . e($r['freelancer_name']) : 'From ' . e($r['client_name']) ?></span>
          <span class="tag <?= $cls ?>"><?= e($txt) ?></span>
        </div>
        <h3><?= e($r['title']) ?></h3>
        <div class="row meta">
          <span class="price"><?= e(kes($r['amount'])) ?></span>
          <span><?= !empty($r['reference']) ? 'M-Pesa ' . e($r['reference']) : '' ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
