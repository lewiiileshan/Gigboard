<p class="tagline muted">Log in to continue.</p>

<form class="card" method="post" action="<?= url('/login') ?>" novalidate>
  <?= csrf_field() ?>

  <label for="email" style="margin-top:0">Email</label>
  <input id="email" name="email" type="email" autocomplete="email" value="<?= e(old('email')) ?>" placeholder="you@example.com">

  <label for="password">Password</label>
  <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Your password">

  <div class="error"><?= $flash && $flash['type'] === 'error' ? e($flash['message']) : '' ?></div>
  <?php if ($flash && $flash['type'] !== 'error'): ?><p class="muted small"><?= e($flash['message']) ?></p><?php endif; ?>

  <button class="btn block" type="submit">Log in</button>
</form>

<p class="muted small">New here?
  <a href="<?= url('/register') ?>" style="font-weight:700">Create an account</a></p>
