<p class="tagline muted">Create your account in a minute.</p>

<form class="card" method="post" action="<?= url('/register') ?>" novalidate data-validate>
  <?= csrf_field() ?>

  <label style="margin-top:0">I want to</label>
  <div class="role-pick">
    <label><input type="radio" name="role" value="freelancer" <?= (old('role') ?: ($_GET['role'] ?? 'freelancer')) === 'freelancer' ? 'checked' : '' ?>>Find work<br><span class="muted small">Freelancer</span></label>
    <label><input type="radio" name="role" value="client" <?= (old('role') ?: ($_GET['role'] ?? '')) === 'client' ? 'checked' : '' ?>>Hire talent<br><span class="muted small">Client</span></label>
  </div>

  <label for="name">Full name</label>
  <input id="name" name="name" autocomplete="name" required minlength="2" maxlength="100" value="<?= e(old('name')) ?>" placeholder="e.g. Mary Wekesa">

  <div class="form-row">
    <div>
      <label for="email">Email</label>
      <input id="email" name="email" type="email" autocomplete="email" required value="<?= e(old('email')) ?>" placeholder="you@example.com">
    </div>
    <div>
      <label for="phone">Phone (optional)</label>
      <input id="phone" name="phone" type="tel" autocomplete="tel" pattern="^\+?[0-9 ]{9,15}$" data-msg="Enter a phone number like 0712 345 678, or leave it blank." value="<?= e(old('phone')) ?>" placeholder="07XX XXX XXX">
    </div>
  </div>

  <div class="form-row">
    <div>
      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8" placeholder="At least 8 characters">
    </div>
    <div>
      <label for="confirm">Confirm password</label>
      <input id="confirm" name="confirm" type="password" autocomplete="new-password" required placeholder="Repeat password">
    </div>
  </div>

  <label style="display:flex;gap:8px;align-items:flex-start;font-weight:400">
    <input id="terms" type="checkbox" name="terms" value="1" required style="width:auto;margin-top:3px">
    <span class="small">I accept the <a href="<?= url('/terms') ?>" target="_blank" style="text-decoration:underline">terms of service</a>.</span>
  </label>

  <div class="error"><?= $flash && $flash['type'] === 'error' ? e($flash['message']) : '' ?></div>
  <button class="btn block" type="submit">Create account</button>
</form>

<p class="muted small">Already registered?
  <a href="<?= url('/login') ?>" style="font-weight:600">Log in</a></p>
