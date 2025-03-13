<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('api-users') ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to API Users
        </a>
        <h2>Create API User</h2>
    </div>
</div>

<!-- Info Alert -->
<div class="alert alert-info mb-4">
    <div class="d-flex align-items-center">
        <i class="fas fa-info-circle me-2"></i>
        <div>
            <strong>Note:</strong> API users are used only for tracking API consumption. They do not have access to this dashboard.
            Each API user will receive a unique identifier for API authentication and usage tracking.
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-user-plus me-1"></i>
        New API User
    </div>
    <div class="card-body">
        <form action="<?= site_url('api-users/create') ?>" method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="user_id" class="form-label">User ID</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="user_id" name="user_id" value="<?= old('user_id') ?>" 
                           pattern="[a-zA-Z0-9_-]+" minlength="3" maxlength="50" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="generateUserId()">
                        <i class="fas fa-random me-1"></i>Generate
                    </button>
                </div>
                <small class="text-muted">Unique identifier for API authentication. Use only letters, numbers, underscores, and hyphens.</small>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= old('name') ?>" required>
                <small class="text-muted">Display name for this API user.</small>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email (Optional)</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>">
                <small class="text-muted">Optional email for notifications.</small>
            </div>

            <div class="mb-3">
                <label for="quota" class="form-label">Token Quota</label>
                <div class="input-group">
                    <input type="number" class="form-control" id="quota" name="quota" value="<?= old('quota', 1000) ?>" min="1" required>
                    <span class="input-group-text">tokens</span>
                </div>
                <small class="text-muted">Maximum number of tokens this API user can consume. Default is 1,000 tokens.</small>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="active" name="active" value="1" <?= old('active', '1') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="active">Active</label>
                </div>
                <small class="text-muted">If unchecked, this API user will not be able to use the API.</small>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>Create API User
            </button>
        </form>
    </div>
</div>

<script>
function generateUserId() {
    // Generate a random string of 8 characters
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';
    for (let i = 0; i < 8; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    
    // Add a timestamp to ensure uniqueness
    const timestamp = new Date().getTime().toString(36);
    const userId = `user_${result}_${timestamp}`;
    
    document.getElementById('user_id').value = userId;
}
</script>

<?= $this->endSection() ?>
