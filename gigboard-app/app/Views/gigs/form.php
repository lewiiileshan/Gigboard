<?php $editing = $gig !== null; $flashShown = true; // errors are shown under the form, not in the top banner ?>
<div class="page-head"><h2 style="margin:0"><?= $editing ? 'Edit gig' : 'Post a gig' ?></h2></div>

<form class="card" method="post" action="<?= e($action) ?>" novalidate>
  <?= csrf_field() ?>

  <label for="title" style="margin-top:0">Title</label>
  <input id="title" name="title" maxlength="150" value="<?= e(old('title', $gig['title'] ?? '')) ?>" placeholder="e.g. Design a logo for my bakery">

  <label for="category_id">Category</label>
  <select id="category_id" name="category_id">
    <option value="">Choose a category</option>
    <?php $selected = old('category_id', (string) ($gig['category_id'] ?? '')); ?>
    <?php foreach ($categories as $c): ?>
      <option value="<?= (int) $c['id'] ?>" <?= $selected === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
    <?php endforeach; ?>
  </select>

  <label for="description">Description</label>
  <textarea id="description" name="description" rows="6" placeholder="What needs to be done? Include sizes, formats, and anything the freelancer should know."><?= e(old('description', $gig['description'] ?? '')) ?></textarea>

  <div class="form-row">
    <div>
      <label for="budget">Budget (KES)</label>
      <input id="budget" name="budget" inputmode="numeric" value="<?= e(old('budget', isset($gig['budget']) ? (string) (int) $gig['budget'] : '')) ?>" placeholder="e.g. 3000">
    </div>
    <div>
      <label for="location">Location</label>
      <input id="location" name="location" maxlength="100" value="<?= e(old('location', $gig['location'] ?? '')) ?>" placeholder="Remote or a town, e.g. Nairobi">
    </div>
  </div>

  <label for="deadline">Deadline (optional)</label>
  <input id="deadline" name="deadline" type="date" value="<?= e(old('deadline', $gig['deadline'] ?? '')) ?>">

  <div class="error"><?= $flash && $flash['type'] === 'error' ? e($flash['message']) : '' ?></div>
  <button class="btn block" type="submit"><?= $editing ? 'Save changes' : 'Post gig' ?></button>
</form>
