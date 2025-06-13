<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('admin/tenants') ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Tenants
        </a>
        <h2>Create New Tenant</h2>
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
        <i class="fas fa-building me-1"></i>
        Tenant Information
    </div>
    <div class="card-body">
        <form action="<?= site_url('admin/tenants/store') ?>" method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="name" class="form-label">Tenant Name</label>
                <input type="text" class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= old('name') ?>" required>
                <?php if (session('errors.name')): ?>
                    <div class="invalid-feedback"><?= session('errors.name') ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="max_api_keys" class="form-label">Max API Keys</label>
                <input type="number" min="1" class="form-control <?= session('errors.max_api_keys') ? 'is-invalid' : '' ?>" id="max_api_keys" name="max_api_keys" value="<?= old('max_api_keys', 1) ?>" required>
                <?php if (session('errors.max_api_keys')): ?>
                    <div class="invalid-feedback"><?= session('errors.max_api_keys') ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control <?= session('errors.email') ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= old('email') ?>" required>
                <?php if (session('errors.email')): ?>
                    <div class="invalid-feedback"><?= session('errors.email') ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="subscription_status" class="form-label">Subscription Status</label>
                <select class="form-select <?= session('errors.subscription_status') ? 'is-invalid' : '' ?>" id="subscription_status" name="subscription_status">
                    <option value="trial" <?= old('subscription_status', 'trial') === 'trial' ? 'selected' : '' ?>>Trial</option>
                    <option value="active" <?= old('subscription_status', '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="expired" <?= old('subscription_status', '') === 'expired' ? 'selected' : '' ?>>Expired</option>
                </select>
                <?php if (session('errors.subscription_status')): ?>
                    <div class="invalid-feedback"><?= session('errors.subscription_status') ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input <?= session('errors.active') ? 'is-invalid' : '' ?>" id="active" name="active" value="1" <?= old('active', '1') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="active">Active</label>
                    <?php if (session('errors.active')): ?>
                        <div class="invalid-feedback"><?= session('errors.active') ?></div>
                    <?php endif; ?>
                </div>
                <small class="text-muted">If unchecked, the tenant will not be able to use the API.</small>
            </div>

            <button type="submit" class="btn btn-primary">Create Tenant</button>
            <a href="<?= site_url('admin/tenants') ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
