<section class="hero">
  <div class="site-nav">
    <span class="brand"><?= logo_tile('sm') ?>GigBoard KE</span>
    <a class="link" href="<?= url('/login') ?>">Log in</a>
  </div>
  <div class="hero-inner">
    <?= logo_tile() ?>
    <h1>GigBoard KE</h1>
    <p class="spaced">Local gigs</p>
    <p class="lead">Post a task or find paid work near you. Built for Kenyan youth, priced in shillings.</p>
    <div class="hero-actions">
      <a class="btn light" href="<?= url('/login') ?>">Log in</a>
      <a class="btn outline-light" href="<?= url('/register') ?>">Register</a>
    </div>
  </div>
</section>

<section class="section">
  <h2>How it works</h2>
  <p class="sub">Three steps from idea to done.</p>
  <div class="grid3">
    <div class="card"><div class="step-n">1</div><h3>Post or browse</h3><p class="muted">Clients post a gig with a budget. Freelancers search by category, budget and location.</p></div>
    <div class="card"><div class="step-n">2</div><h3>Apply and hire</h3><p class="muted">Freelancers send a short proposal and price. The client picks the best fit.</p></div>
    <div class="card"><div class="step-n">3</div><h3>Deliver and review</h3><p class="muted">Work is delivered and approved, payment is tracked, and both sides leave a rating.</p></div>
  </div>
</section>

<section class="section">
  <div class="grid3" style="grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
    <div class="card"><h3>For clients</h3>
      <ul class="checklist"><li>Post a gig in under a minute</li><li>Compare proposals and prices</li><li>See each freelancer's ratings</li></ul></div>
    <div class="card"><h3>For freelancers</h3>
      <ul class="checklist"><li>Find gigs that match your skills</li><li>Build a profile and a track record</li><li>Know exactly what is paid and pending</li></ul></div>
  </div>
</section>

<?php if ($categories): ?>
<section class="section">
  <h2>Popular categories</h2>
  <p class="sub">From design to deliveries.</p>
  <div class="chips">
    <?php foreach ($categories as $c): ?><span class="chip"><?= e($c['name']) ?></span><?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="cta">
  <div class="card">
    <h2>Ready to get started?</h2>
    <div class="actions" style="justify-content:center">
      <a class="btn light" href="<?= url('/register') ?>">Create a free account</a>
    </div>
  </div>
</section>

<footer class="site-footer">© <?= date('Y') ?> GigBoard KE · A Mount Kenya University project</footer>
