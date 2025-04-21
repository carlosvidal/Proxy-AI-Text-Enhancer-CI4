<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/users') ?>" class="btn btn-secondary ms-2">Cancel</a>
        <a href="<?= site_url('admin/tenants/users/' . $tenant['tenant_id']) ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to API Users
        </a>
        <h2>Edit API User for <?= esc($tenant['name']) ?></h2>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-user-edit me-1"></i>
        Edit API User
    </div>
    <div class="card-body">
        <form action="<?= site_url('admin/tenants/users/' . $tenant['tenant_id'] . '/edit/' . $user['user_id']) ?>" method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="user_id" class="form-label">User ID</label>
                <input type="text" class="form-control" id="user_id" value="<?= esc($user['user_id']) ?>" readonly>
                <small class="text-muted">The user ID cannot be changed once created.</small>
            </div>

            <div class="mb-3">
                <label for="external_id" class="form-label">External ID</label>
                <input type="text" class="form-control" id="external_id" value="<?= esc($user['external_id'] ?? '') ?>" readonly>
                <small class="text-muted">The external ID cannot be changed once created.</small>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= old('name', $user['name'] ?? '') ?>" required>
                <small class="text-muted">Display name for this API user.</small>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email (Optional)</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= old('email', $user['email'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="quota" class="form-label">Token Quota</label>
                <input type="number" class="form-control" id="quota" name="quota" value="<?= old('quota', $user['quota']) ?>" min="1" required>
                <small class="text-muted">Number of tokens this API user can consume.</small>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="active" name="active" value="1" <?= old('active', $user['active']) === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="active">Active</label>
                </div>
                <small class="text-muted">If unchecked, this API user will not be able to use the API.</small>
            </div>

            <button type="submit" class="btn btn-primary">Update API User</button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
