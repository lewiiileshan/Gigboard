<?php
$user = \App\Core\Auth::user();
$nav  = $nav ?? '';
$isClient = $user && $user['role'] === 'client';

// Links shown in the top bar on a laptop and as the bottom tab bar on a phone.
$isAdmin  = $user && $user['role'] === 'admin';
if ($isAdmin) {
    $tabs = [['admin', 'Overview', '/admin', 'home'], ['admin-users', 'Users', '/admin/users', 'users'], ['admin-gigs', 'Gigs', '/admin/gigs', 'briefcase'], ['account', 'Account', '/account', 'user']];
} else {
    $tabs = [['dashboard', 'Home', '/dashboard', 'home'], ['gigs', $isClient ? 'Find talent' : 'Find work', '/gigs', 'search']];
    if ($isClient)  $tabs[] = ['mine', 'My gigs', '/my-gigs', 'briefcase'];
    if (!$isClient) $tabs[] = ['applied', 'Applied', '/my-applications', 'briefcase'];
    $tabs[] = ['payments', 'Payments', '/payments', 'wallet'];
    $tabs[] = ['account', 'Account', '/account', 'user'];
}
$activeTab = $nav === 'post' ? 'mine' : $nav;      // the "post a gig" page belongs under My gigs
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#05285f">
  <title><?= e($title ?? 'GigBoard KE') ?> · GigBoard KE</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap">
  <link rel="stylesheet" href="<?= url('css/app.css') ?>">
</head>
<body>
  <a class="skip" href="#main">Skip to content</a>
  <header class="topbar">
    <div class="topbar-in">
      <a class="brand" href="<?= url($isAdmin ? '/admin' : '/dashboard') ?>"><?= logo_tile('sm') ?>GigBoard KE</a>
      <?php if ($user): ?>
        <nav class="topnav" aria-label="Main">
          <?php foreach ($tabs as [$key, $label, $href]): if ($key === 'account') continue; ?>
            <a href="<?= url($href) ?>" class="<?= $activeTab === $key ? 'active' : '' ?>"<?= $activeTab === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
          <?php endforeach; ?>
        </nav>
        <?php if (!$isAdmin): ?>
        <form class="head-search" method="get" action="<?= url('/gigs') ?>" role="search">
          <?= icon('search') ?>
          <label class="sr" for="head-q">Search gigs</label>
          <input id="head-q" name="q" placeholder="Search gigs">
          <button class="btn sm ghost" type="submit">Search</button>
        </form>
        <?php endif; ?>
        <div class="head-actions">
          <?php if ($isClient): ?><a class="btn sm" href="<?= url('/gigs/create') ?>"><?= icon('plus') ?>Post a gig</a><?php endif; ?>
          <details class="menu-wrap">
            <summary class="avatar" aria-label="Open account menu" title="Account menu"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></summary>
            <div class="dropdown">
              <div class="who"><strong><?= e($user['name']) ?></strong><div class="muted small"><?= e(ucfirst($user['role'])) ?></div></div>
              <a href="<?= url('/account') ?>">Account</a>
              <form method="post" action="<?= url('/logout') ?>"><?= csrf_field() ?>
                <button class="menu-out" type="submit">Sign out</button>
              </form>
            </div>
          </details>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <main class="view" id="main">
    <?php if ($flash && empty($flashShown)): ?>
      <div class="card" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>" style="border-left:4px solid var(--<?= $flash['type'] === 'error' ? 'danger' : 'ok' ?>)">
        <?= e($flash['message']) ?>
      </div>
    <?php endif; ?>
    <?= $content ?>
  </main>

  <?php if ($user): ?>
    <nav class="bottom-nav" aria-label="Main">
      <?php foreach ($tabs as [$key, $label, $href, $ico]): ?>
        <a href="<?= url($href) ?>" class="<?= $activeTab === $key ? 'active' : '' ?>">
          <span class="pill"><?= icon($ico) ?></span><?= e($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>
  <script src="<?= url('js/app.js') ?>" defer></script>
</body>
</html>
