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
  <div class="auth-wrap">
    <div class="auth-card">
      <a class="auth-brand" href="<?= url('/') ?>"><?= logo_tile() ?>GigBoard KE</a>
      <?= $content ?>
    </div>
  </div>
</body>
</html>
