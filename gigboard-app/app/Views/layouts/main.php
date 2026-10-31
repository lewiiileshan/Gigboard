<?php
$user = \App\Core\Auth::user();
$nav  = $nav ?? '';
$isClient = $user && $user['role'] === 'client';

// Tabs shown at the bottom of the phone screen and, as text links, at the top on a laptop.
$tabs = [['dashboard', 'Home', '/dashboard', 'home'], ['gigs', 'Gigs', '/gigs', 'search']];
if ($isClient) $tabs[] = ['mine', 'My gigs', '/my-gigs', 'briefcase'];
if (!$isClient) $tabs[] = ['applied', 'Applied', '/my-applications', 'briefcase'];
$tabs[] = ['account', 'Account', '/account', 'user'];
$activeTab = $nav === 'post' ? 'mine' : $nav;      // the "post a gig" page belongs under My gigs
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#05285f">
  <title><?= e($title ?? 'GigBoard KE') ?> · GigBoard KE</title>
  <link rel="stylesheet" href="<?= url('css/app.css') ?>">
</head>
<body>
  <header class="topbar">
    <a class="brand" href="<?= url('/dashboard') ?>"><?= logo_tile('sm') ?>GigBoard KE</a>
    <?php if ($user): ?>
      <nav class="topnav">
        <?php foreach ($tabs as [$key, $label, $href]): ?>
          <a href="<?= url($href) ?>" class="<?= $activeTab === $key ? 'active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>
      <a class="avatar" href="<?= url('/account') ?>" title="Account"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></a>
    <?php endif; ?>
  </header>

  <main class="view">
    <?php if ($flash && empty($flashShown)): ?>
      <div class="card" style="border-left:5px solid var(--<?= $flash['type'] === 'error' ? 'danger' : 'ok' ?>)">
        <?= e($flash['message']) ?>
      </div>
    <?php endif; ?>
    <?= $content ?>
  </main>

  <?php if ($user): ?>
    <nav class="bottom-nav">
      <?php foreach ($tabs as [$key, $label, $href, $ico]): ?>
        <a href="<?= url($href) ?>" class="<?= $activeTab === $key ? 'active' : '' ?>">
          <span class="pill"><?= icon($ico) ?></span><?= e($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>
</body>
</html>
