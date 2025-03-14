<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>API Users</h2>
        <p class="text-muted">Manage API users and their button access</p>
    </div>
    <div>
        <a href="<?= site_url('api-users/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Create API User
        </a>
    </div>
</div>

<?php if (session()->has('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= session('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (session()->has('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (empty($users)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <div class="display-6 text-muted mb-4">
                <i class="fas fa-users"></i>
            </div>
            <h5 class="text-muted">No API Users Yet</h5>
            <p class="text-muted">Create your first API user to start using the API</p>
            <a href="<?= site_url('api-users/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Create API User
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <i class="fas fa-users me-1"></i>
            API Users
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Monthly Usage</th>
                            <th>Status</th>
                            <th>Last Activity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <code><?= esc($user['user_id']) ?></code>
                                    <button class="btn btn-sm btn-outline-secondary ms-1" 
                                            onclick="copyToClipboard('<?= esc($user['user_id']) ?>')"
                                            title="Copy to clipboard">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </td>
                                <td>
                                    <?= esc($user['name']) ?: '<span class="text-muted">Not set</span>' ?>
                                </td>
                                <td>
                                    <?= esc($user['email']) ?: '<span class="text-muted">Not set</span>' ?>
                                </td>
                                <td>
                                    <?php 
                                    $usagePercent = ($user['monthly_usage'] / $user['quota']) * 100;
                                    $barClass = $usagePercent >= 90 ? 'bg-danger' : ($usagePercent >= 75 ? 'bg-warning' : 'bg-success');
                                    ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 5px;">
                                            <div class="progress-bar <?= $barClass ?>" 
                                                 role="progressbar" 
                                                 style="width: <?= min($usagePercent, 100) ?>%">
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            <?= round($usagePercent) ?>%
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="status_<?= $user['user_id'] ?>"
                                               <?= $user['active'] ? 'checked' : '' ?>
                                               onchange="toggleStatus('<?= $user['user_id'] ?>', this.checked)">
                                    </div>
                                </td>
                                <td>
                                    <?php if ($user['last_activity']): ?>
                                        <span title="<?= date('Y-m-d H:i:s', strtotime($user['last_activity'])) ?>">
                                            <?= date('Y-m-d H:i', strtotime($user['last_activity'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?= site_url('api-users/view/' . $user['user_id']) ?>" 
                                           class="btn btn-sm btn-outline-secondary"
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="confirmDelete('<?= $user['user_id'] ?>')"
                                                title="Delete User">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete API User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this API user? This action cannot be undone.</p>
                <p class="text-danger">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    All associated usage data will be permanently deleted.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteButton" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
const modal = new bootstrap.Modal(document.getElementById('deleteModal'));

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show a brief visual feedback
        const btn = event.currentTarget;
        const icon = btn.querySelector('i');
        const originalClass = icon.className;
        
        icon.className = 'fas fa-check';
        btn.classList.add('btn-success');
        btn.classList.remove('btn-outline-secondary');
        
        setTimeout(() => {
            icon.className = originalClass;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-secondary');
        }, 1000);
    });
}

function confirmDelete(userId) {
    document.getElementById('deleteButton').href = `<?= site_url('api-users/delete/') ?>${userId}`;
    modal.show();
}

function toggleStatus(userId, active) {
    fetch(`<?= site_url('api-users/toggleStatus/') ?>${userId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ active: active ? 1 : 0 })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            // Revert the switch if the update failed
            document.getElementById(`status_${userId}`).checked = !active;
            alert(data.message || 'Failed to update status');
        }
    })
    .catch(error => {
        // Revert the switch if there was an error
        document.getElementById(`status_${userId}`).checked = !active;
        alert('Failed to update status');
    });
}
</script>

<?= $this->endSection() ?>
