<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('admin/tenants/view/' . $tenant['id']) ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Tenant
        </a>
        <h2>API Users for <?= esc($tenant['name']) ?></h2>
    </div>
    <a href="<?= site_url('admin/tenants/users/' . $tenant['id'] . '/create') ?>" class="btn btn-primary">
        <i class="fas fa-user-plus me-1"></i>Add API User
    </a>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-users me-1"></i>
        API User List
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
            <p class="text-muted text-center">No API users found for this tenant.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Quota</th>
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
                                <td><?= number_format($user['quota']) ?> tokens</td>
                                <td>
                                    <?php if ($user['active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?= site_url('admin/tenants/users/' . $tenant['id'] . '/usage/' . $user['id']) ?>" 
                                           class="btn btn-sm btn-outline-info" 
                                           title="View Usage">
                                            <i class="fas fa-chart-bar"></i>
                                        </a>
                                        <a href="<?= site_url('admin/tenants/users/' . $tenant['id'] . '/edit/' . $user['id']) ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= site_url('admin/tenants/users/' . $tenant['id'] . '/delete/' . $user['id']) ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           title="Delete User" 
                                           onclick="return confirm('Are you sure you want to delete this API user?')">
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