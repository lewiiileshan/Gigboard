<div class="profile-head">
  <span class="avatar lg"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
  <div>
    <h2><?= e($user['name']) ?></h2>
    <span class="tag"><?= $user['role'] === 'client' ? 'Client' : 'Freelancer' ?></span>
  </div>
</div>

<div class="card">
  <div class="info-row"><span class="muted">Email</span><strong><?= e($user['email']) ?></strong></div>
  <div class="info-row"><span class="muted">Phone</span><strong><?= $user['phone'] ? e($user['phone']) : 'Not added' ?></strong></div>
  <div class="info-row"><span class="muted">Member since</span><strong><?= e(date('d M Y', strtotime($user['created_at']))) ?></strong></div>
</div>

<div class="card menu">
  <a href="<?= url('/gigs') ?>"><?= icon('search') ?>Browse gigs<?= icon('chevron') ?></a>
  <?php if ($user['role'] === 'client'): ?>
    <a href="<?= url('/gigs/create') ?>"><?= icon('plus') ?>Post a gig<?= icon('chevron') ?></a>
    <a href="<?= url('/my-gigs') ?>"><?= icon('briefcase') ?>My gigs<?= icon('chevron') ?></a>
  <?php endif; ?>
  <form method="post" action="<?= url('/logout') ?>" style="border-top:1px solid var(--border)">
    <?= csrf_field() ?>
    <button type="submit" class="danger"><?= icon('logout') ?>Log out</button>
  </form>
</div>
