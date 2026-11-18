<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#05285f">
  <meta name="description" content="GigBoard KE: find local gigs, hire local talent. Built for Kenyan youth.">
  <title><?= e($title ?? 'GigBoard KE') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap">
  <link rel="stylesheet" href="<?= url('css/app.css') ?>">
</head>
<body>
  <header class="site-head">
    <div class="site-head-in">
      <a class="brand" href="<?= url('/') ?>"><?= logo_tile('sm') ?>GigBoard KE</a>
      <nav class="site-links" aria-label="Site">
        <a href="<?= url('/#how') ?>">How it works</a>
        <a href="<?= url('/#categories') ?>">Categories</a>
      </nav>
      <div class="head-actions" style="margin-left:auto">
        <a class="link" href="<?= url('/login') ?>">Log in</a>
        <a class="btn sm" href="<?= url('/register') ?>">Sign up</a>
      </div>
    </div>
  </header>
  <?= $content ?>
  <footer class="site-footer">
    <div class="foot-in">
      <div><a class="brand" href="<?= url('/') ?>"><?= logo_tile('sm') ?>GigBoard KE</a>
        <p class="muted" style="margin-top:10px;max-width:320px">Local gigs for Kenyan youth. Post a task or find paid work, priced in shillings.</p></div>
      <div><h4>Clients</h4><a href="<?= url('/register?role=client') ?>">Post a gig</a><a href="<?= url('/login') ?>">Log in</a></div>
      <div><h4>Freelancers</h4><a href="<?= url('/register?role=freelancer') ?>">Find work</a><a href="<?= url('/login') ?>">Log in</a></div>
      <div><h4>Legal</h4><a href="<?= url('/terms') ?>">Terms of service</a><a href="<?= url('/privacy') ?>">Privacy policy</a></div>
    </div>
    <div class="foot-note">© <?= date('Y') ?> GigBoard KE · A Mount Kenya University project</div>
  </footer>
  <script src="<?= url('js/app.js') ?>" defer></script>
</body>
</html>
