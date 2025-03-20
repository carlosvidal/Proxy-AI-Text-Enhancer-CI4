<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?= $title ?></h1>
    
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-users me-1"></i>
                            API Users
                        </div>
                        <div>
                            <a href="<?= site_url('api-users/create') ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i>Create API User
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-success">
                            <?= session()->getFlashdata('success') ?>
                        </div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger">
                            <?= session()->getFlashdata('error') ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($users)): ?>
                        <div class="alert alert-info">
                            No API users found. Click the button above to create one.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>External ID</th>
                                        <th>Email</th>
                                        <th>Monthly Usage</th>
                                        <th>Daily Usage</th>
                                        <th>Status</th>
                                        <th>Last Activity</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= esc($user['name']) ?></td>
                                            <td><code><?= esc($user['external_id']) ?></code></td>
                                            <td><?= $user['email'] ? esc($user['email']) : '<em>Not set</em>' ?></td>
                                            <td>
                                                <?php 
                                                $monthlyUsage = $user['monthly_usage'] ?? 0;
                                                $monthlyPercent = ($monthlyUsage / $user['quota']) * 100;
                                                $monthlyClass = $monthlyPercent >= 90 ? 'danger' : ($monthlyPercent >= 75 ? 'warning' : 'success');
                                                ?>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1 me-2" style="height: 5px;">
                                                        <div class="progress-bar bg-<?= $monthlyClass ?>" 
                                                             role="progressbar" 
                                                             style="width: <?= min($monthlyPercent, 100) ?>%">
                                                        </div>
                                                    </div>
                                                    <small><?= number_format($monthlyUsage) ?> / <?= number_format($user['quota']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                $dailyUsage = $user['daily_usage'] ?? 0;
                                                $dailyQuota = $user['daily_quota'] ?? 10000;
                                                $dailyPercent = ($dailyUsage / $dailyQuota) * 100;
                                                $dailyClass = $dailyPercent >= 90 ? 'danger' : ($dailyPercent >= 75 ? 'warning' : 'success');
                                                ?>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1 me-2" style="height: 5px;">
                                                        <div class="progress-bar bg-<?= $dailyClass ?>" 
                                                             role="progressbar" 
                                                             style="width: <?= min($dailyPercent, 100) ?>%">
                                                        </div>
                                                    </div>
                                                    <small><?= number_format($dailyUsage) ?> / <?= number_format($dailyQuota) ?></small>
                                                </div>
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
                                                    <?= date('Y-m-d H:i:s', strtotime($user['last_activity'])) ?>
                                                <?php else: ?>
                                                    <em>Never</em>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="<?= site_url('api-users/view/' . $user['user_id']) ?>" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= site_url('api-users/edit/' . $user['user_id']) ?>" 
                                                       class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger" 
                                                            onclick="confirmDelete('<?= $user['user_id'] ?>')">
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
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this API user? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $this->section('scripts') ?>
<script>
function confirmDelete(userId) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    document.getElementById('deleteForm').action = `<?= site_url('api-users/delete/') ?>${userId}`;
    modal.show();
}
</script>
<?php $this->endSection() ?>

<?php $this->endSection() ?>
