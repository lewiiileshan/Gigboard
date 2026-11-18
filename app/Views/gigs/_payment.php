<?php
$tagClass = ['pending' => 'warn', 'paid' => 'warn', 'confirmed' => 'ok', 'disputed' => 'danger'][$payment['status']] ?? '';
$tagText  = ['pending' => 'Not paid yet', 'paid' => 'Paid, awaiting confirmation', 'confirmed' => 'Confirmed', 'disputed' => 'Reported as not received'][$payment['status']] ?? $payment['status'];
$done = $gig['status'] === 'completed';
?>
<h3 class="section-title">Payment</h3>
<div class="card">
  <div class="row">
    <div><div class="muted small">Agreed amount</div><strong class="price"><?= e(kes($payment['amount'])) ?></strong></div>
    <span class="tag <?= $tagClass ?>"><?= e($tagText) ?></span>
  </div>
  <?php if (!empty($payment['reference'])): ?>
    <p class="muted small" style="margin:10px 0 0">M-Pesa code: <strong><?= e($payment['reference']) ?></strong></p>
  <?php endif; ?>
  <p class="muted small" style="margin:10px 0 0"><?= icon('wallet') ?> GigBoard only keeps a record. It does not move money: the client pays the freelancer directly, then records it here.</p>
</div>

<?php if (!$done): ?>
  <p class="muted small">Payment is recorded after the client approves the work.</p>

<?php elseif ($isOwner && in_array($payment['status'], ['pending', 'disputed'], true)): ?>
  <form class="card" method="post" action="<?= url('/payments/' . (int) $payment['id'] . '/paid') ?>" novalidate data-validate>
    <?= csrf_field() ?>
    <?php if ($payment['status'] === 'disputed'): ?>
      <p style="margin-top:0"><strong><?= e($hired['freelancer_name']) ?> says this payment has not arrived.</strong> Check it, then record it again.</p>
    <?php endif; ?>
    <h3 style="margin:0 0 4px">Record that you paid <?= e($hired['freelancer_name']) ?></h3>
    <label for="ref">M-Pesa code (optional)</label>
    <input id="ref" name="reference" maxlength="10" pattern="[A-Za-z0-9]{10}" data-msg="An M-Pesa code has 10 letters and numbers, like QJK7X8Y9ZA. Leave it blank if you do not have it." autocomplete="off" autocapitalize="characters" placeholder="e.g. QJK7X8Y9ZA">
    <button class="btn block" type="submit" style="margin-top:14px">Mark as paid</button>
  </form>

<?php elseif ($isOwner && $payment['status'] === 'paid'): ?>
  <p class="muted small">Waiting for <?= e($hired['freelancer_name']) ?> to confirm they received it.</p>

<?php elseif ($isHired && $payment['status'] === 'paid'): ?>
  <div class="card">
    <h3 style="margin:0 0 4px">Did the money arrive?</h3>
    <p class="muted small" style="margin-top:0">The client says they paid you.</p>
    <form method="post" action="<?= url('/payments/' . (int) $payment['id'] . '/confirm') ?>">
      <?= csrf_field() ?><button class="btn block" type="submit">Yes, I received it</button>
    </form>
    <form method="post" action="<?= url('/payments/' . (int) $payment['id'] . '/dispute') ?>"
          data-confirm-title="Report this payment as not received?" data-confirm-text="The client will be asked to check it again." data-confirm-action="Report as not received" data-confirm-back="Go back" style="margin-top:10px">
      <?= csrf_field() ?><button class="btn danger block" type="submit">No, I have not received it</button>
    </form>
  </div>

<?php elseif ($isHired && $payment['status'] === 'disputed'): ?>
  <p class="muted small">You reported this payment as not received. The client has been asked to check it.</p>

<?php elseif ($payment['status'] === 'confirmed'): ?>
  <p class="muted small"><strong>Payment complete.</strong> Both sides have confirmed it.</p>

<?php elseif ($isHired): ?>
  <p class="muted small">The client has not recorded a payment yet.</p>
<?php endif; ?>
