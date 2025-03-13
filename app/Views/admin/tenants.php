<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Tenants List -->
<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Tenants</h2>
            <a href="<?= site_url('admin/tenants/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Add Tenant
            </a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <i class="fas fa-building me-1"></i>
                Tenant List
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
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
                                    <a href="<?= site_url('admin/tenants/view/' . $tenant['tenant_id']) ?>" class="text-decoration-none">
                                        <?= esc($tenant['name']) ?>
                                    </a>
                                </td>
                                <td><code><?= esc($tenant['tenant_id']) ?></code></td>
                                <td><?= esc($tenant['email'] ?? 'N/A') ?></td>
                                <td>
                                    <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/users') ?>" class="text-decoration-none">
                                        <span class="badge bg-primary"><?= $tenant['api_users'] ?? 0 ?> users</span>
                                    </a>
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
                                        <?= number_format($tenant['total_requests'] ?? 0) ?> requests
                                    </small>
                                    <small class="d-block text-muted">
                                        <?= number_format($tenant['total_tokens'] ?? 0) ?> tokens
                                    </small>
                                </td>
                                <td><?= date('M j, Y', strtotime($tenant['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?= site_url('admin/tenants/view/' . $tenant['tenant_id']) ?>" 
                                           class="btn btn-sm btn-outline-info" 
                                           title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= site_url('admin/tenants/edit/' . $tenant['tenant_id']) ?>" 
                                           class="btn btn-sm btn-outline-primary"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= site_url('admin/tenants/delete/' . $tenant['tenant_id']) ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Are you sure you want to delete this tenant? This will also delete all associated API users and data.')"
                                           title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($tenants)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No tenants found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
