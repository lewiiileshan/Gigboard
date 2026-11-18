<div class="page-head"><h2>Users</h2></div>

<form class="card" method="get" action="<?= url('/admin/users') ?>">
  <div class="form-row">
    <div><label for="q">Name or email</label><input id="q" name="q" value="<?= e($q) ?>"></div>
    <div><label for="role">Role</label>
      <select id="role" name="role">
        <option value="">All roles</option>
        <?php foreach (['client' => 'Clients', 'freelancer' => 'Freelancers', 'admin' => 'Admins'] as $k => $label): ?>
          <option value="<?= $k ?>" <?= $role === $k ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select></div>
  </div>
  <div class="actions" style="margin-top:14px"><button class="btn" type="submit">Search</button><a class="btn ghost" href="<?= url('/admin/users') ?>">Clear</a></div>
</form>

<p class="muted small" style="margin:10px 2px"><?= count($users) ?> <?= count($users) === 1 ? 'user' : 'users' ?> shown</p>
<?php if (!$users): ?>
  <div class="empty">No users match.</div>
<?php else: ?>
  <div class="stack">
  <?php foreach ($users as $u): ?>
    <div class="card">
      <div class="row">
        <div style="min-width:0"><strong><?= e($u['name']) ?></strong>
          <span class="meta-label"><?= e(ucfirst($u['role'])) ?></span>
          <?php if ((int) $u['suspended'] === 1): ?><span class="tag danger">Suspended</span><?php endif; ?>
          <div class="muted small file-name"><?= e($u['email']) ?> · joined <?= e(date('d M Y', strtotime($u['created_at']))) ?></div></div>
        <?php if ($u['role'] !== 'admin'): ?>
          <?php $suspended = (int) $u['suspended'] === 1; ?>
          <form method="post" action="<?= url('/admin/users/' . (int) $u['id'] . ($suspended ? '/restore' : '/suspend')) ?>"
                <?php if (!$suspended): ?>data-confirm-title="Suspend <?= e($u['name']) ?>?" data-confirm-text="They are signed out at once and their open gigs are hidden until you restore them." data-confirm-action="Suspend user"<?php endif; ?>>
            <?= csrf_field() ?>
            <button class="btn sm <?= $suspended ? 'ghost' : 'danger' ?>" type="submit"><?= $suspended ? 'Restore' : 'Suspend' ?></button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
