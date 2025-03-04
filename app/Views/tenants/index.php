<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Tenant List</h2>
    <a href="<?= site_url('tenants/create') ?>" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Create Tenant
    </a>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-building me-1"></i>
        All Tenants
    </div>
    <div class="card-body">
        <?php if (empty($tenants)): ?>
            <p class="text-muted text-center">No tenants found in the system.</p>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Quota</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tenants as $tenant): ?>
                        <tr>
                            <td><?= $tenant['id'] ?></td>
                            <td><?= esc($tenant['name']) ?></td>
                            <td><?= esc($tenant['email']) ?></td>
                            <td><?= number_format($tenant['quota']) ?></td>
                            <td>
                                <?php if ($tenant['active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('Y-m-d', strtotime($tenant['created_at'])) ?></td>
                            <td>
                                <a href="<?= site_url('tenants/view/' . $tenant['id']) ?>" class="btn btn-sm btn-info text-white" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= site_url('tenants/edit/' . $tenant['id']) ?>" class="btn btn-sm btn-warning text-white" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= site_url('tenants/users/' . $tenant['id']) ?>" class="btn btn-sm btn-primary" title="Manage Users">
                                    <i class="fas fa-users"></i>
                                </a>
                                <a href="<?= site_url('tenants/delete/' . $tenant['id']) ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this tenant?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>