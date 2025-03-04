<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="alert alert-danger" role="alert">
    <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i> Error!</h4>
    <p><?= isset($error) ? $error : 'An unknown error occurred.' ?></p>
    <hr>
    <p class="mb-0">
        Please ensure that the database migrations have been run successfully.
        <a href="<?= site_url('migrate') ?>" class="alert-link">Run migrations now</a>.
    </p>
</div>

<?= $this->endSection() ?>