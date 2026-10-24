<div class="card" style="text-align:center">
  <h2 style="margin-top:0"><?= e((string) ($code ?? '')) ?></h2>
  <p><?= e($message ?? 'Something went wrong.') ?></p>
  <p><a href="<?= url('/') ?>">Go back home</a></p>
</div>
