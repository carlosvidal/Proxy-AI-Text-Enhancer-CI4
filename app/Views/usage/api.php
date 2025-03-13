<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4">
    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1><?= $title ?></h1>
                <a href="/tenants/add_user" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>
                    Add API User
                </a>
            </div>
        </div>
    </div>

    <div class="card mt-4 mb-4">
        <div class="card-header">
            <i class="fas fa-users me-1"></i>
            API Users
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Quota</th>
                        <th>Usage</th>
                        <th>% Used</th>
                        <th>Status</th>
                        <th>Last Used</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($api_users as $user): ?>
                    <tr>
                        <td><?= esc($user->user_id) ?></td>
                        <td><?= esc($user->name) ?></td>
                        <td><?= esc($user->email) ?? '-' ?></td>
                        <td><?= number_format($user->quota) ?></td>
                        <td>
                            <div class="d-flex flex-column">
                                <span><?= number_format($user->total_tokens) ?> tokens</span>
                                <small class="text-muted"><?= number_format($user->request_count) ?> requests</small>
                            </div>
                        </td>
                        <td>
                            <?php $percentage = ($user->total_tokens / $user->quota) * 100; ?>
                            <div class="progress">
                                <div class="progress-bar <?= $percentage > 90 ? 'bg-danger' : ($percentage > 70 ? 'bg-warning' : 'bg-success') ?>" 
                                     role="progressbar" 
                                     style="width: <?= min(100, $percentage) ?>%"
                                     aria-valuenow="<?= $percentage ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    <?= number_format($percentage, 1) ?>%
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($user->active): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user->last_used): ?>
                                <?= date('Y-m-d H:i', strtotime($user->last_used)) ?>
                            <?php else: ?>
                                Never
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="/usage/user/<?= $user->user_id ?>" class="btn btn-sm btn-info" title="View Usage">
                                    <i class="fas fa-chart-line"></i>
                                </a>
                                <a href="/tenants/edit_user/<?= $user->user_id ?>" class="btn btn-sm btn-primary" title="Edit User">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" title="Delete User" 
                                        onclick="confirmDelete('<?= $user->user_id ?>', '<?= esc($user->name) ?>')">
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete API User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the API user "<span id="userName"></span>"?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteButton" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(userId, userName) {
    document.getElementById('userName').textContent = userName;
    document.getElementById('deleteButton').href = '/tenants/delete_user/' + userId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
<?= $this->endSection() ?>
