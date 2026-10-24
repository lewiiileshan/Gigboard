<div class="page-head">
  <h2 style="margin:0">Browse gigs</h2>
  <p class="muted"><?= (int) $total ?> open <?= $total === 1 ? 'gig' : 'gigs' ?> found</p>
</div>

<form class="card" method="get" action="<?= url('/gigs') ?>">
  <div class="filters">
    <div class="wide">
      <label for="q">Search</label>
      <input id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="e.g. logo, translation, delivery">
    </div>
    <div>
      <label for="category">Category</label>
      <select id="category" name="category">
        <option value="0">All categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= $filters['category'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="location">Location</label>
      <input id="location" name="location" value="<?= e($filters['location']) ?>" placeholder="e.g. Nairobi, Remote">
    </div>
    <div>
      <label for="min">Min budget (KES)</label>
      <input id="min" name="min" inputmode="numeric" value="<?= e($filters['min']) ?>" placeholder="0">
    </div>
    <div>
      <label for="max">Max budget (KES)</label>
      <input id="max" name="max" inputmode="numeric" value="<?= e($filters['max']) ?>" placeholder="any">
    </div>
    <div class="wide">
      <label for="sort">Sort by</label>
      <select id="sort" name="sort">
        <?php foreach ($sorts as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= $filters['sort'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <button class="btn" type="submit">Search</button>
  <a class="btn ghost" href="<?= url('/gigs') ?>">Clear</a>
</form>

<?php if (!$gigs): ?>
  <div class="empty"><span class="ico">🔍</span>No gigs match your search. Try fewer filters.</div>
<?php else: ?>
  <div class="stack">
    <?php foreach ($gigs as $g) { include __DIR__ . '/_card.php'; } ?>
  </div>

  <?php if ($pages > 1):
    $query = array_filter($filters, fn($v) => $v !== '' && $v !== 0 && $v !== 'newest');
    $link  = fn(int $n) => url('/gigs') . '?' . http_build_query($query + ['page' => $n]); ?>
    <div class="pager">
      <?php if ($page > 1): ?><a class="btn sm ghost" href="<?= e($link($page - 1)) ?>">← Previous</a><?php endif; ?>
      <span class="muted small">Page <?= (int) $page ?> of <?= (int) $pages ?></span>
      <?php if ($page < $pages): ?><a class="btn sm ghost" href="<?= e($link($page + 1)) ?>">Next →</a><?php endif; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
