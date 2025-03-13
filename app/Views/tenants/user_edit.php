<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('api-users') ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to API Users
        </a>
        <h2>Edit API User</h2>
    </div>
</div>

<!-- Usage Statistics -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4 text-center">
                        <h6 class="text-muted mb-1">Token Usage</h6>
                        <h3 class="mb-0"><?= number_format($user['usage']) ?></h3>
                        <small class="text-muted">tokens used</small>
                    </div>
                    <div class="col-md-4 text-center">
                        <h6 class="text-muted mb-1">Quota</h6>
                        <h3 class="mb-0"><?= number_format($user['quota']) ?></h3>
                        <small class="text-muted">total tokens</small>
                    </div>
                    <div class="col-md-4 text-center">
                        <?php
                        $remaining = max(0, $user['quota'] - $user['usage']);
                        $percentage = $user['quota'] > 0 ? ($user['usage'] / $user['quota'] * 100) : 0;
                        $statusClass = $percentage >= 90 ? 'text-danger' : ($percentage >= 75 ? 'text-warning' : 'text-success');
                        ?>
                        <h6 class="text-muted mb-1">Remaining</h6>
                        <h3 class="mb-0 <?= $statusClass ?>"><?= number_format($remaining) ?></h3>
                        <small class="text-muted">tokens available</small>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="progress" style="height: 8px;">
                        <?php $barClass = $percentage >= 90 ? 'bg-danger' : ($percentage >= 75 ? 'bg-warning' : 'bg-success'); ?>
                        <div class="progress-bar <?= $barClass ?>" 
                             role="progressbar" 
                             style="width: <?= $percentage ?>%"
                             aria-valuenow="<?= $percentage ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100"></div>
                    </div>
                    <div class="small text-muted text-center mt-1">
                        <?= number_format($percentage, 1) ?>% of quota used
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-user-edit me-1"></i>
        API User Information
    </div>
    <div class="card-body">
        <form action="<?= site_url('api-users/edit/' . $user['id']) ?>" method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="user_id" class="form-label">User ID</label>
                <input type="text" class="form-control" id="user_id" value="<?= esc($user['user_id']) ?>" readonly>
                <small class="text-muted">The user ID cannot be changed once created.</small>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= old('name', $user['name']) ?>" required>
                <small class="text-muted">Display name for this API user.</small>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email (Optional)</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= old('email', $user['email']) ?>">
                <small class="text-muted">Optional email for notifications.</small>
            </div>

            <div class="mb-3">
                <label for="quota" class="form-label">Token Quota</label>
                <div class="input-group">
                    <input type="number" class="form-control" id="quota" name="quota" value="<?= old('quota', $user['quota']) ?>" min="<?= $user['usage'] ?>" required>
                    <span class="input-group-text">tokens</span>
                </div>
                <small class="text-muted">Minimum quota must be greater than or equal to current usage (<?= number_format($user['usage']) ?> tokens).</small>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="active" name="active" value="1" <?= old('active', $user['active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="active">Active</label>
                </div>
                <small class="text-muted">If unchecked, this API user will not be able to use the API.</small>
            </div>

            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Update API User
                </button>
                <a href="<?= site_url('api-users/delete/' . $user['id']) ?>" 
                   class="btn btn-outline-danger"
                   onclick="return confirm('Are you sure you want to delete this API user? This action cannot be undone and will invalidate any existing API tokens for this user.')">
                    <i class="fas fa-trash me-1"></i>Delete API User
                </a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
