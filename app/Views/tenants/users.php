<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>API Users</h2>
        <p class="text-muted mb-0">Manage API access tokens and usage quotas</p>
    </div>
    <a href="<?= site_url('api-users/create') ?>" class="btn btn-primary">
        <i class="fas fa-user-plus me-1"></i>Add API User
    </a>
</div>

<!-- API Usage Summary -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="alert alert-info">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle me-2"></i>
                <div>
                    <strong>Note:</strong> API users are for tracking API consumption only. They do not have access to this dashboard.
                    Each API user has their own quota and usage tracking.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-users me-1"></i>
        API User List
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="text-center py-4">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <p class="text-muted">No API users found. Create your first API user to start tracking API usage.</p>
                <a href="<?= site_url('api-users/create') ?>" class="btn btn-primary">
                    <i class="fas fa-user-plus me-1"></i>Add API User
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Quota</th>
                            <th>Usage</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><code><?= esc($user['user_id']) ?></code></td>
                                <td><?= esc($user['name']) ?></td>
                                <td><?= esc($user['email'] ?? '-') ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2"><?= number_format($user['quota']) ?></div>
                                        <div class="small text-muted">tokens</div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $usage = $user['usage'] ?? 0;
                                    $percentage = $user['quota'] > 0 ? ($usage / $user['quota'] * 100) : 0;
                                    $barClass = $percentage >= 90 ? 'bg-danger' : ($percentage >= 75 ? 'bg-warning' : 'bg-success');
                                    ?>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1 me-2" style="min-width: 100px;">
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar <?= $barClass ?>" 
                                                     role="progressbar" 
                                                     style="width: <?= $percentage ?>%"
                                                     aria-valuenow="<?= $percentage ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="small text-muted">
                                            <?= number_format($usage) ?> used
                                        </div>
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
                                    <div class="small text-muted">
                                        <?= date('M j, Y', strtotime($user['created_at'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?= site_url('api-users/edit/' . $user['id']) ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= site_url('api-users/delete/' . $user['id']) ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           title="Delete User" 
                                           onclick="return confirm('Are you sure you want to delete this API user? This action cannot be undone and will invalidate any existing API tokens for this user.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
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

<?= $this->endSection() ?>
