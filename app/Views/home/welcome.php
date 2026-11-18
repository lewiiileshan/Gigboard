<?php
$catIcons = ['Graphic Design' => 'pen', 'Writing & Translation' => 'doc', 'Data Entry' => 'folder', 'Photo & Video' => 'camera',
             'Web & Software' => 'code', 'Social Media' => 'users', 'Errands & Delivery' => 'truck'];
?>
<section class="hero">
  <div class="hero-in">
    <div>
      <h1>Find local gigs. Hire local talent.</h1>
      <p class="lead">GigBoard KE connects Kenyan clients with young freelancers for design, writing, errands, software and more. Prices are in shillings and payment is tracked in M-Pesa terms.</p>
      <div class="actions"><a class="btn light lg" href="<?= url('/register?role=freelancer') ?>">Find work</a><a class="btn outline-light lg" href="<?= url('/register?role=client') ?>">Post a gig</a></div>
      <?php if ($categories): ?>
        <div class="popular"><span>Popular:</span>
          <?php foreach (array_slice($categories, 0, 4) as $c): ?><a href="<?= url('/register?role=freelancer') ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="role-cards">
      <div class="role-card"><h3>I want to hire</h3><p>Post a gig, set your requirements and choose from qualified applicants.</p>
        <a class="btn block" href="<?= url('/register?role=client') ?>">Post a gig</a></div>
      <div class="role-card"><h3>I want to work</h3><p>Browse open gigs, show your qualifications and get hired near you.</p>
        <a class="btn ghost block" href="<?= url('/register?role=freelancer') ?>">Find work</a></div>
    </div>
  </div>
</section>

<?php if ($categories): ?>
<section class="section" id="categories">
  <h2>Browse by category</h2>
  <p class="sub">Work that young Kenyans already do well.</p>
  <div class="cat-grid">
    <?php foreach ($categories as $c): ?>
      <a class="cat-tile" href="<?= url('/register?role=freelancer') ?>"><?= icon($catIcons[$c['name']] ?? 'briefcase') ?><?= e($c['name']) ?></a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($featured)): ?>
<section class="section">
  <h2>Latest open gigs</h2>
  <p class="sub">Sign up to apply.</p>
  <div class="stack">
    <?php foreach ($featured as $g): ?>
      <div class="card gig-row">
        <div class="top">
          <div><span class="meta-label"><?= e($g['category_name']) ?></span>
            <h3 style="margin-top:8px"><?= e($g['title']) ?></h3></div>
          <span class="price"><?= e(kes($g['budget'])) ?></span>
        </div>
        <p class="muted clamp" style="margin-top:6px"><?= e($g['description']) ?></p>
        <div class="meta"><span><?= icon('pin') ?><?= e($g['location']) ?></span><span><?= icon('clock') ?><?= e(time_ago($g['created_at'])) ?></span></div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section" id="how">
  <h2>How it works</h2>
  <p class="sub">Four steps from idea to done.</p>
  <div class="grid3" style="grid-template-columns:repeat(auto-fit,minmax(230px,1fr))">
    <div class="card"><div class="step-n">1</div><h3>Post or browse</h3><p class="muted">Clients post a gig with a budget and requirements. Freelancers search by category, budget and location.</p></div>
    <div class="card"><div class="step-n">2</div><h3>Apply with proof</h3><p class="muted">Freelancers send a proposal and attach qualification files that only the client can see.</p></div>
    <div class="card"><div class="step-n">3</div><h3>Hire and deliver</h3><p class="muted">The client picks one freelancer. Finished work is uploaded privately and approved by the client.</p></div>
    <div class="card"><div class="step-n">4</div><h3>Track payment</h3><p class="muted">Record the M-Pesa payment, confirm receipt and keep a clear history of what is paid.</p></div>
  </div>
</section>

<section class="section">
  <h2>Built around trust</h2>
  <p class="sub">What the platform does today.</p>
  <div class="grid3">
    <div class="card"><?= icon('lock') ?><h3 style="margin-top:8px">Private delivery</h3><p class="muted">Work files are stored privately. Only the client and the hired freelancer can download them.</p></div>
    <div class="card"><?= icon('doc') ?><h3 style="margin-top:8px">Proof of qualifications</h3><p class="muted">Applicants upload certificates or samples so clients choose with confidence.</p></div>
    <div class="card"><?= icon('wallet') ?><h3 style="margin-top:8px">Clear payment status</h3><p class="muted">Pending, paid and confirmed are tracked for every hired gig, with an M-Pesa code check.</p></div>
  </div>
</section>

<section class="cta">
  <div class="card">
    <h2>Ready to get started?</h2>
    <div class="actions" style="justify-content:center">
      <a class="btn light lg" href="<?= url('/register') ?>">Create a free account</a>
    </div>
  </div>
</section>
