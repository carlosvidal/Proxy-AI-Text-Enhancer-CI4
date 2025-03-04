<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('tenants') ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Tenants
        </a>
        <h2>Edit Tenant</h2>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-building-user me-1"></i>
        Edit Tenant
    </div>
    <div class="card-body">
        <form action="<?= site_url('tenants/edit/' . $tenant['id']) ?>" method="post">
            <?= csrf_field() ?>

            <?php if (isset($validation)): ?>
                <div class="alert alert-danger">
                    <?= $validation->listErrors() ?>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= set_value('name', $tenant['name']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= set_value('email', $tenant['email']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="quota" class="form-label">Default Quota</label>
                <input type="number" class="form-control" id="quota" name="quota" value="<?= set_value('quota', $tenant['quota']) ?>" required>
                <div class="form-text">Default token quota for this tenant's users</div>
            </div>

            <div class="mb-3">
                <label for="active" class="form-label">Status</label>
                <select class="form-select" id="active" name="active">
                    <option value="1" <?= $tenant['active'] ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= !$tenant['active'] ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= site_url('tenants') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Tenant</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>