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

<div class="card">
    <div class="card-header">
        <i class="fas fa-building me-1"></i>
        Tenant Information
    </div>
    <div class="card-body">
        <form action="<?= site_url('admin/tenants/create') ?>" method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="tenant_id" class="form-label">Tenant ID</label>
                <input type="text" class="form-control <?= session('errors.tenant_id') ? 'is-invalid' : '' ?>" id="tenant_id" name="tenant_id" value="<?= old('tenant_id') ?>" required>
                <?php if (session('errors.tenant_id')): ?>
                    <div class="invalid-feedback"><?= session('errors.tenant_id') ?></div>
                <?php endif; ?>
                <small class="text-muted">A unique identifier for the tenant. This cannot be changed once created.</small>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= old('name') ?>" required>
                <?php if (session('errors.name')): ?>
                    <div class="invalid-feedback"><?= session('errors.name') ?></div>
                <?php endif; ?>
                <small class="text-muted">Display name for this tenant.</small>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control <?= session('errors.email') ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= old('email') ?>" required>
                <?php if (session('errors.email')): ?>
                    <div class="invalid-feedback"><?= session('errors.email') ?></div>
                <?php endif; ?>
                <small class="text-muted">Primary contact email for this tenant.</small>
            </div>

            <div class="mb-3">
                <label for="subscription_status" class="form-label">Subscription Status</label>
                <select class="form-select" id="subscription_status" name="subscription_status">
                    <option value="trial" <?= old('subscription_status') === 'trial' ? 'selected' : '' ?>>Trial</option>
                    <option value="active" <?= old('subscription_status') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="expired" <?= old('subscription_status') === 'expired' ? 'selected' : '' ?>>Expired</option>
                </select>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="active" name="active" value="1" <?= old('active', '1') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="active">Active</label>
                </div>
                <small class="text-muted">If unchecked, this tenant will not be able to access the system.</small>
            </div>

            <button type="submit" class="btn btn-primary">Create Tenant</button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
