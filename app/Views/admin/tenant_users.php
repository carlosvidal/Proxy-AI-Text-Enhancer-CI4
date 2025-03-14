<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('admin/tenants/view/' . $tenant['tenant_id']) ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Tenant
        </a>
        <h2>API Users for <?= esc($tenant['name']) ?></h2>
    </div>
    <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/users/create') ?>" class="btn btn-primary">
        <i class="fas fa-user-plus me-1"></i>Add API User
    </a>
</div>

<?php if (session()->has('error')): ?>
    <div class="alert alert-danger"><?= session('error') ?></div>
<?php endif; ?>

<?php if (session()->has('success')): ?>
    <div class="alert alert-success"><?= session('success') ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="alert alert-info">No API users found for this tenant.</div>
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= esc($user['user_id']) ?></td>
                                <td><?= esc($user['name']) ?></td>
                                <td><?= esc($user['email'] ?? 'N/A') ?></td>
                                <td><?= number_format($user['quota']) ?></td>
                                <td>
                                    <?php if ($user['active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/users/' . $user['user_id'] . '/edit') ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/users/' . $user['user_id'] . '/usage') ?>" 
                                           class="btn btn-sm btn-outline-info" title="View Usage">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger"
                                                title="Delete User"
                                                onclick="confirmDelete('<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/users/' . $user['user_id'] . '/delete') ?>')">
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

<script>
function confirmDelete(url) {
    if (confirm('Are you sure you want to delete this API user? This action cannot be undone.')) {
        window.location.href = url;
    }
}
</script>

<?= $this->endSection() ?>