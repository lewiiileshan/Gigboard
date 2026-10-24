<div class="hero-card">
  <div class="hello">Welcome back</div>
  <h2><?= e($user['name']) ?></h2>
  <?php if ($counts !== null): ?>
    <div class="big"><?= (int) $counts['open'] ?></div>
    <div class="big-lbl">open <?= $counts['open'] === 1 ? 'gig' : 'gigs' ?> right now</div>
    <a class="btn light" href="<?= url('/gigs/create') ?>"><?= icon('plus') ?>Post a gig</a>
  <?php else: ?>
    <div class="big"><?= (int) $open ?></div>
    <div class="big-lbl">open <?= $open === 1 ? 'gig' : 'gigs' ?> to apply for</div>
    <a class="btn light" href="<?= url('/gigs') ?>"><?= icon('search') ?>Browse gigs</a>
  <?php endif; ?>
</div>

<?php if ($counts !== null): ?>
  <div class="stats">
    <div class="card stat"><div class="num"><?= (int) $counts['open'] ?></div><div class="lbl">Open</div></div>
    <div class="card stat"><div class="num"><?= (int) $counts['in_progress'] ?></div><div class="lbl">In progress</div></div>
    <div class="card stat"><div class="num"><?= (int) $counts['completed'] ?></div><div class="lbl">Completed</div></div>
    <div class="card stat"><div class="num"><?= (int) $counts['cancelled'] ?></div><div class="lbl">Cancelled</div></div>
  </div>
  <a class="btn ghost block" href="<?= url('/my-gigs') ?>">View all my gigs</a>
<?php endif; ?>
