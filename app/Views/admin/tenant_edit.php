<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('admin/tenants') ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Tenants
        </a>
        <h2>Edit Tenant: <?= esc($tenant['name']) ?></h2>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-building me-1"></i>
        Tenant Information
    </div>
    <div class="card-body">
        <form action="<?= site_url('admin/tenants/edit/' . $tenant['id']) ?>" method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="name" class="form-label">Tenant Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= old('name', $tenant['name']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="tenant_id" class="form-label">Tenant ID</label>
                <input type="text" class="form-control" id="tenant_id" name="tenant_id" value="<?= old('tenant_id', $tenant['tenant_id']) ?>" readonly>
                <small class="text-muted">The tenant ID cannot be changed once created.</small>
            </div>

            <div class="mb-3">
                <label for="subscription_status" class="form-label">Subscription Status</label>
                <select class="form-select" id="subscription_status" name="subscription_status">
                    <option value="trial" <?= old('subscription_status', $tenant['subscription_status']) === 'trial' ? 'selected' : '' ?>>Trial</option>
                    <option value="active" <?= old('subscription_status', $tenant['subscription_status']) === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="expired" <?= old('subscription_status', $tenant['subscription_status']) === 'expired' ? 'selected' : '' ?>>Expired</option>
                </select>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="active" name="active" value="1" <?= old('active', $tenant['active']) === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="active">Active</label>
                </div>
                <small class="text-muted">If unchecked, the tenant will not be able to use the API.</small>
            </div>

            <button type="submit" class="btn btn-primary">Update Tenant</button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
