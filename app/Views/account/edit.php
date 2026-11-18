<?php $flashShown = true; $isF = $user['role'] === 'freelancer'; ?>
<div class="page-head"><h2 style="margin:0">Edit profile</h2></div>

<form class="card" method="post" action="<?= url('/account/edit') ?>" novalidate data-validate>
  <?= csrf_field() ?>

  <label for="name" style="margin-top:0">Full name</label>
  <input id="name" name="name" maxlength="100" minlength="2" required autocomplete="name" value="<?= e(old('name', $user['name'])) ?>">

  <label for="phone">Phone (optional)</label>
  <input id="phone" name="phone" type="tel" autocomplete="tel" value="<?= e(old('phone', (string) $user['phone'])) ?>" placeholder="07XX XXX XXX">

  <?php if ($isF): ?>
    <h3 style="margin:22px 0 2px">About your work</h3>
    <p class="muted small" style="margin-top:0">Clients see this when you apply for their gigs.</p>

    <label for="headline">Headline</label>
    <input id="headline" name="headline" maxlength="80" autocomplete="off" value="<?= e(old('headline', (string) ($profile['headline'] ?? ''))) ?>" placeholder="e.g. Graphic designer, 3 years of logo work">

    <label for="town">Town</label>
    <input id="town" name="town" maxlength="100" autocomplete="address-level2" value="<?= e(old('town', (string) ($profile['town'] ?? ''))) ?>" placeholder="e.g. Nairobi">

    <label for="bio">Bio</label>
    <textarea id="bio" name="bio" rows="5" maxlength="1000" placeholder="What do you do, and what have you done before?"><?= e(old('bio', (string) ($profile['bio'] ?? ''))) ?></textarea>

    <label for="skills">Skills (separate with commas, up to 8)</label>
    <input id="skills" name="skills" autocomplete="off" value="<?= e(old('skills', (string) ($profile['skills'] ?? ''))) ?>" placeholder="e.g. Logo design, Branding, Photoshop">

    <div class="form-row">
      <div>
        <label for="hourly_rate">Hourly rate in KES (optional)</label>
        <input id="hourly_rate" name="hourly_rate" inputmode="numeric" autocomplete="off" value="<?= e(old('hourly_rate', isset($profile['hourly_rate']) && $profile['hourly_rate'] !== null ? (string) (int) $profile['hourly_rate'] : '')) ?>" placeholder="e.g. 500">
      </div>
      <div>
        <label for="portfolio_url">Portfolio link (optional)</label>
        <input id="portfolio_url" name="portfolio_url" type="url" autocomplete="url" value="<?= e(old('portfolio_url', (string) ($profile['portfolio_url'] ?? ''))) ?>" placeholder="https://…">
      </div>
    </div>
  <?php endif; ?>

  <div class="error"><?= $flash && $flash['type'] === 'error' ? e($flash['message']) : '' ?></div>
  <button class="btn block" type="submit">Save profile</button>
  <a class="btn ghost block" style="margin-top:10px" href="<?= url('/account') ?>">Cancel</a>
</form>
