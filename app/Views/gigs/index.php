<?php
$base   = url('/gigs');
$active = [];   // filter chips: label => link without that filter
$without = function (string $key) use ($filters, $base) {
  $q = array_filter($filters, fn($v, $k) => $k !== $key && $v !== '' && $v !== 0 && $v !== 'newest', ARRAY_FILTER_USE_BOTH);
  return $base . ($q ? '?' . http_build_query($q) : '');
};
$catName = '';
foreach ($categories as $c) if ($filters['category'] === (int) $c['id']) $catName = $c['name'];
if ($filters['q'] !== '')        $active['"' . $filters['q'] . '"'] = $without('q');
if ($catName !== '')             $active[$catName] = $without('category');
if ($filters['location'] !== '') $active[$filters['location']] = $without('location');
if ($filters['min'] !== '')      $active['From KES ' . $filters['min']] = $without('min');
if ($filters['max'] !== '')      $active['Up to KES ' . $filters['max']] = $without('max');
?>
<div class="page-head">
  <h2>Find work</h2>
  <p class="muted"><?= (int) $total ?> open <?= $total === 1 ? 'gig' : 'gigs' ?> found</p>
</div>

<div class="browse">
  <form class="card filter-panel" method="get" action="<?= $base ?>">
    <details <?= $active ? 'open' : '' ?> id="filters">
      <summary><span class="ttl">Filters</span><?= icon('chevron') ?></summary>
      <label for="q">Keyword</label>
      <input id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="e.g. logo, translation">
      <label for="category">Category</label>
      <select id="category" name="category">
        <option value="0">All categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= $filters['category'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <label for="location">Location</label>
      <input id="location" name="location" value="<?= e($filters['location']) ?>" placeholder="e.g. Nairobi, Remote">
      <label for="min">Budget (KES)</label>
      <div class="pair">
        <input id="min" name="min" inputmode="numeric" aria-label="Minimum budget" value="<?= e($filters['min']) ?>" placeholder="Min">
        <input id="max" name="max" inputmode="numeric" aria-label="Maximum budget" value="<?= e($filters['max']) ?>" placeholder="Max">
      </div>
      <label for="sort">Sort by</label>
      <select id="sort" name="sort">
        <?php foreach ($sorts as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= $filters['sort'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="actions" style="margin-top:16px">
        <button class="btn" type="submit">Search</button>
        <a class="btn ghost" href="<?= $base ?>">Clear</a>
      </div>
    </details>
  </form>

  <div>
    <?php if ($active): ?>
      <div class="chips-row">
        <?php foreach ($active as $label => $href): ?>
          <span class="fchip"><?= e($label) ?><a href="<?= e($href) ?>" aria-label="Remove filter <?= e($label) ?>">×</a></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!$gigs): ?>
      <div class="empty"><span class="ico"><?= icon('search') ?></span>No gigs match your search. Try fewer filters.<br>
        <a class="btn ghost sm" style="margin-top:12px" href="<?= $base ?>">Clear all filters</a></div>
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
  </div>
</div>
<script>
  // On a laptop the filters are always open; on a phone they fold away behind the "Filters" heading.
  if (window.matchMedia('(min-width: 800px)').matches) document.getElementById('filters').open = true;
</script>
