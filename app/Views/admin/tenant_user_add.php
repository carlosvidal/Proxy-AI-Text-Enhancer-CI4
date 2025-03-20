<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('admin/tenants/users/' . $tenant['tenant_id']) ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to API Users
        </a>
        <h2>Add API User for <?= esc($tenant['name']) ?></h2>
    </div>
</div>

<?php if (session()->has('error')): ?>
    <div class="alert alert-danger"><?= session('error') ?></div>
<?php endif; ?>

<?php if (session()->has('errors')): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach (session('errors') as $error): ?>
                <li><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-user-plus me-1"></i>
        API User Information
    </div>
    <div class="card-body">
        <form action="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/users/store') ?>" method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="external_id" class="form-label">External ID <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= session('errors.external_id') ? 'is-invalid' : '' ?>" id="external_id" name="external_id" value="<?= old('external_id') ?>" required>
                <?php if (session('errors.external_id')): ?>
                    <div class="invalid-feedback"><?= session('errors.external_id') ?></div>
                <?php endif; ?>
                <small class="text-muted">Unique identifier for this user (typically user ID from external system)</small>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= old('name') ?>" required>
                <?php if (session('errors.name')): ?>
                    <div class="invalid-feedback"><?= session('errors.name') ?></div>
                <?php endif; ?>
                <small class="text-muted">Display name for this API user.</small>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email (Optional)</label>
                <input type="email" class="form-control <?= session('errors.email') ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= old('email') ?>">
                <?php if (session('errors.email')): ?>
                    <div class="invalid-feedback"><?= session('errors.email') ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="quota" class="form-label">Token Quota</label>
                <input type="number" class="form-control <?= session('errors.quota') ? 'is-invalid' : '' ?>" id="quota" name="quota" value="<?= old('quota', 1000) ?>" min="1" required>
                <?php if (session('errors.quota')): ?>
                    <div class="invalid-feedback"><?= session('errors.quota') ?></div>
                <?php endif; ?>
                <small class="text-muted">Number of tokens this API user can consume.</small>
            </div>

            <button type="submit" class="btn btn-primary">Create API User</button>
            <a href="<?= site_url('admin/tenants/users/' . $tenant['tenant_id']) ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
