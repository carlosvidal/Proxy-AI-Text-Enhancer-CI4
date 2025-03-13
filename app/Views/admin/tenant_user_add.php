<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('admin/tenants/users/' . $tenant['id']) ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to API Users
        </a>
        <h2>Add API User for <?= esc($tenant['name']) ?></h2>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-user-plus me-1"></i>
        API User Information
    </div>
    <div class="card-body">
        <form action="<?= site_url('admin/tenants/users/' . $tenant['id'] . '/create') ?>" method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="user_id" class="form-label">User ID</label>
                <input type="text" class="form-control" id="user_id" name="user_id" value="<?= old('user_id') ?>" required>
                <small class="text-muted">A unique identifier for this API user. This will be used for API authentication.</small>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= old('name') ?>" required>
                <small class="text-muted">Display name for this API user.</small>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email (Optional)</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>">
            </div>

            <div class="mb-3">
                <label for="quota" class="form-label">Token Quota</label>
                <input type="number" class="form-control" id="quota" name="quota" value="<?= old('quota', 1000) ?>" min="1" required>
                <small class="text-muted">Number of tokens this API user can consume.</small>
            </div>

            <button type="submit" class="btn btn-primary">Create API User</button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
