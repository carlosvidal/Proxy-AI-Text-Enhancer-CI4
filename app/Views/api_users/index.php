<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><?= lang('App.api_users_title') ?></h2>
        <p class="text-muted"><?= lang('App.api_users_subtitle') ?></p>
    </div>
    <div>
        <a href="<?= site_url('api-users/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i><?= lang('App.api_users_create') ?>
        </a>
    </div>
</div>

<?php if (session()->has('success')): ?>
    <div class="alert alert-success">
        <?= session('success') ?>
    </div>
<?php endif; ?>

<?php if (session()->has('error')): ?>
    <div class="alert alert-danger">
        <?= session('error') ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-users me-1"></i>
        <?= lang('App.api_users_title') ?>
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="alert alert-info">
                <?= lang('App.api_users_empty') ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><?= lang('App.api_users_user_id') ?></th>
                            <th><?= lang('App.api_users_name') ?></th>
                            <th><?= lang('App.api_users_email') ?></th>
                            <th><?= lang('App.api_users_quota') ?></th>
                            <th><?= lang('App.api_users_usage_month') ?></th>
                            <th><?= lang('App.api_users_usage_total') ?></th>
                            <th><?= lang('App.api_users_buttons') ?></th>
                            <th><?= lang('App.api_users_status') ?></th>
                            <th><?= lang('App.api_users_last_activity') ?></th>
                            <th><?= lang('App.api_users_actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><code><?= esc($user['user_id']) ?></code></td>
                                <td><?= esc($user['name']) ?: '<span class="text-muted">Not set</span>' ?></td>
                                <td><?= esc($user['email']) ?: '<span class="text-muted">Not set</span>' ?></td>
                                <td>
                                    <?= number_format($user['quota']) ?> tokens
                                    <?php 
                                    $usagePercent = ($user['monthly_usage'] / $user['quota']) * 100;
                                    $barClass = $usagePercent >= 90 ? 'bg-danger' : ($usagePercent >= 75 ? 'bg-warning' : 'bg-success');
                                    ?>
                                    <div class="progress mt-1" style="height: 5px;">
                                        <div class="progress-bar <?= $barClass ?>" 
                                             role="progressbar" 
                                             style="width: <?= min($usagePercent, 100) ?>%">
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?= number_format($user['monthly_usage']) ?> tokens
                                    <?php if ($user['monthly_usage'] >= $user['quota']): ?>
                                        <span class="badge bg-danger ms-1">Quota Exceeded</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= number_format($user['usage']['total_tokens']) ?> tokens<br>
                                    <small class="text-muted">
                                        <?= number_format($user['usage']['total_requests']) ?> requests
                                    </small>
                                </td>
                                <td>
                                    <?php if (!empty($user['buttons'])): ?>
                                        <?php foreach ($user['buttons'] as $button): ?>
                                            <span class="badge bg-secondary"><?= esc($button['name']) ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No buttons assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['last_activity']): ?>
                                        <?= date('Y-m-d H:i', strtotime($user['last_activity'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?= site_url('api-users/view/' . $user['user_id']) ?>" 
                                           class="btn btn-sm btn-info" 
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm <?= $user['active'] ? 'btn-warning' : 'btn-success' ?>"
                                                onclick="toggleStatus('<?= $user['user_id'] ?>', <?= $user['active'] ? 0 : 1 ?>)"
                                                title="<?= $user['active'] ? 'Deactivate' : 'Activate' ?>">
                                            <i class="fas fa-<?= $user['active'] ? 'ban' : 'check' ?>"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger"
                                                onclick="confirmDelete('<?= $user['user_id'] ?>')"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang('App.api_users_delete_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?= lang('App.api_users_delete_message') ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('App.cancel') ?></button>
                <form action="" method="post" id="deleteForm" style="display: inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger"><?= lang('App.delete') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(userId) {
    const modal = document.getElementById('deleteModal');
    const form = document.getElementById('deleteForm');
    form.action = `<?= site_url('api-users/delete/') ?>${userId}`;
    new bootstrap.Modal(modal).show();
}

function toggleStatus(userId, status) {
    if (confirm(`<?= lang('App.api_users_toggle_status_confirm') ?>`)) {
        fetch(`<?= site_url('api-users/toggle-status/') ?>${userId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?= csrf_hash() ?>'
            },
            body: JSON.stringify({ active: status })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('<?= lang('App.error_updating_status') ?>: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('<?= lang('App.error_updating_status_try_again') ?>');
        });
    }
}
</script>

<?= $this->endSection() ?>
