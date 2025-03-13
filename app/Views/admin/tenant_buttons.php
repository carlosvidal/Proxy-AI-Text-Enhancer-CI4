<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('admin/tenants/view/' . $tenant['tenant_id']) ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Tenant
        </a>
        <h2>Manage Buttons - <?= esc($tenant['name']) ?></h2>
        <p class="text-muted mb-0">Create and manage text enhancement buttons</p>
    </div>
    <div>
        <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/buttons/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Create Button
        </a>
    </div>
</div>

<?php if (session()->has('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= session('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->has('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Buttons List -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-code me-1"></i>
        Buttons
    </div>
    <div class="card-body">
        <?php if (empty($buttons)): ?>
            <div class="text-center py-4">
                <i class="fas fa-code fa-3x text-muted mb-3"></i>
                <p class="text-muted">No buttons found for this tenant.</p>
                <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/buttons/create') ?>" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Create First Button
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th class="text-end">Requests</th>
                            <th class="text-end">Tokens</th>
                            <th class="text-end">Avg Tokens/Request</th>
                            <th class="text-end">Max Tokens</th>
                            <th class="text-end">Unique Users</th>
                            <th>Last Used</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($buttons as $button): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <strong><?= esc($button['name']) ?></strong>
                                            <?php if (!empty($button['description'])): ?>
                                                <div class="small text-muted"><?= esc($button['description']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= ucfirst($button['type'] ?? 'Standard') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($button['active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?= number_format($button['usage']['total_requests'] ?? 0) ?>
                                </td>
                                <td class="text-end">
                                    <?= number_format($button['usage']['total_tokens'] ?? 0) ?>
                                </td>
                                <td class="text-end">
                                    <?= number_format($button['usage']['avg_tokens_per_request'] ?? 0, 1) ?>
                                </td>
                                <td class="text-end">
                                    <?= number_format($button['usage']['max_tokens'] ?? 0) ?>
                                </td>
                                <td class="text-end">
                                    <?= number_format($button['usage']['unique_users'] ?? 0) ?>
                                </td>
                                <td>
                                    <?php if (!empty($button['last_used'])): ?>
                                        <small class="text-muted">
                                            <?= date('M j, Y g:i A', strtotime($button['last_used'])) ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">Never</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/buttons/' . $button['id'] . '/edit') ?>" 
                                           class="btn btn-sm btn-outline-primary"
                                           title="Edit Button">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/buttons/' . $button['id'] . '/delete') ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Are you sure you want to delete this button? This will also delete all associated usage logs.')"
                                           title="Delete Button">
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
