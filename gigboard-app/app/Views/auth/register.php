<p class="tagline muted">Create your account in a minute.</p>

<form class="card" method="post" action="<?= url('/register') ?>" novalidate>
  <?= csrf_field() ?>

  <label style="margin-top:0">I want to</label>
  <div class="role-pick">
    <label><input type="radio" name="role" value="freelancer" <?= old('role', 'freelancer') === 'freelancer' ? 'checked' : '' ?>>💼 Find work<br><span class="muted small">Freelancer</span></label>
    <label><input type="radio" name="role" value="client" <?= old('role') === 'client' ? 'checked' : '' ?>>🧑‍💼 Hire talent<br><span class="muted small">Client</span></label>
  </div>

  <label for="name">Full name</label>
  <input id="name" name="name" autocomplete="name" value="<?= e(old('name')) ?>" placeholder="e.g. Mary Wekesa">

  <div class="form-row">
    <div>
      <label for="email">Email</label>
      <input id="email" name="email" type="email" autocomplete="email" value="<?= e(old('email')) ?>" placeholder="you@example.com">
    </div>
    <div>
      <label for="phone">Phone (optional)</label>
      <input id="phone" name="phone" type="tel" autocomplete="tel" value="<?= e(old('phone')) ?>" placeholder="07XX XXX XXX">
    </div>
  </div>

  <div class="form-row">
    <div>
      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="new-password" placeholder="At least 8 characters">
    </div>
    <div>
      <label for="confirm">Confirm password</label>
      <input id="confirm" name="confirm" type="password" autocomplete="new-password" placeholder="Repeat password">
    </div>
  </div>

  <label style="display:flex;gap:8px;align-items:flex-start;font-weight:400">
    <input type="checkbox" name="terms" value="1" style="width:auto;margin-top:3px">
    <span class="small">I accept the terms of service.</span>
  </label>

  <div class="error"><?= $flash && $flash['type'] === 'error' ? e($flash['message']) : '' ?></div>
  <button class="btn block" type="submit">Create account</button>
</form>

<p class="muted small">Already registered?
  <a href="<?= url('/login') ?>" style="font-weight:700">Log in</a></p>
