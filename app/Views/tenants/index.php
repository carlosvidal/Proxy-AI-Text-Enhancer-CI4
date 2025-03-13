<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Tenant List</h2>
    <a href="<?= site_url('admin/tenants/create') ?>" class="btn btn-primary">
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
                        <th>Name</th>
                        <th>Tenant ID</th>
                        <th>Email</th>
                        <th>API Users</th>
                        <th>Status</th>
                        <th>Subscription</th>
                        <th>Usage</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tenants as $tenant): ?>
                        <tr>
                            <td>
                                <a href="<?= site_url('admin/tenants/view/' . $tenant['id']) ?>" class="text-decoration-none">
                                    <?= esc($tenant['name']) ?>
                                </a>
                            </td>
                            <td><code><?= esc($tenant['tenant_id']) ?></code></td>
                            <td><?= esc($tenant['email']) ?></td>
                            <td>
                                <span class="badge bg-primary"><?= $tenant['api_users'] ?> users</span>
                            </td>
                            <td>
                                <?php if ($tenant['active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status = strtolower($tenant['subscription_status'] ?? 'trial');
                                if ($status === 'trial') {
                                    $statusClass = 'bg-info';
                                } elseif ($status === 'active') {
                                    $statusClass = 'bg-success';
                                } elseif ($status === 'expired') {
                                    $statusClass = 'bg-danger';
                                } else {
                                    $statusClass = 'bg-secondary';
                                }
                                ?>
                                <span class="badge <?= $statusClass ?>">
                                    <?= esc(ucfirst($tenant['subscription_status'] ?? 'Trial')) ?>
                                </span>
                            </td>
                            <td>
                                <small class="d-block text-muted">
                                    <?= number_format($tenant['total_requests']) ?> requests
                                </small>
                                <small class="d-block text-muted">
                                    <?= number_format($tenant['total_tokens']) ?> tokens
                                </small>
                            </td>
                            <td><?= date('Y-m-d', strtotime($tenant['created_at'])) ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= site_url('admin/tenants/view/' . $tenant['id']) ?>" class="btn btn-sm btn-outline-secondary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= site_url('admin/tenants/edit/' . $tenant['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= site_url('admin/tenants/users/' . $tenant['id']) ?>" class="btn btn-sm btn-outline-info" title="Manage API Users">
                                        <i class="fas fa-users"></i>
                                    </a>
                                    <a href="<?= site_url('admin/tenants/delete/' . $tenant['id']) ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Are you sure you want to delete this tenant?')"
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>