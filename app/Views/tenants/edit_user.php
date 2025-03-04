<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('tenants/users/' . $tenant['id']) ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Users
        </a>
        <h2>Edit User for <?= esc($tenant['name']) ?></h2>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-user-edit me-1"></i>
        Edit User
    </div>
    <div class="card-body">
        <form action="<?= site_url('tenants/edit_user/' . $user->id) ?>" method="post">
            <?= csrf_field() ?>

            <?php if (isset($validation)): ?>
                <div class="alert alert-danger">
                    <?= $validation->listErrors() ?>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="user_id" class="form-label">User ID</label>
                <input type="text" class="form-control" id="user_id" name="user_id" value="<?= set_value('user_id', $user->user_id) ?>" readonly>
                <div class="form-text">User ID cannot be changed</div>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= set_value('name', $user->name) ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= set_value('email', $user->email) ?>" required>
            </div>

            <div class="mb-3">
                <label for="quota" class="form-label">Token Quota</label>
                <input type="number" class="form-control" id="quota" name="quota" value="<?= set_value('quota', $user->quota) ?>" required>
                <div class="form-text">Number of tokens allowed for this user</div>
            </div>

            <div class="mb-3">
                <label for="active" class="form-label">Status</label>
                <select class="form-select" id="active" name="active">
                    <option value="1" <?= $user->active ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= !$user->active ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= site_url('tenants/users/' . $tenant['id']) ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>