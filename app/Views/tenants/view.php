<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('admin/tenants') ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Tenants
        </a>
        <h2><?= esc($tenant['name']) ?> <span class="badge badge-tenant"><?= esc($tenant['tenant_id']) ?></span></h2>
    </div>
    <div>
        <a href="<?= site_url('admin/tenants/edit/' . $tenant['id']) ?>" class="btn btn-warning text-white">
            <i class="fas fa-edit me-1"></i>Edit
        </a>
        <a href="<?= site_url('admin/tenants/users/' . $tenant['id']) ?>" class="btn btn-primary">
            <i class="fas fa-users me-1"></i>Manage API Users
        </a>
        <a href="<?= site_url('admin/buttons/' . $tenant['id']) ?>" class="btn btn-info text-white">
            <i class="fas fa-puzzle-piece me-1"></i>Manage Buttons
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-info-circle me-1"></i>
                Tenant Information
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th style="width: 30%">Name:</th>
                        <td><?= esc($tenant['name']) ?></td>
                    </tr>
                    <tr>
                        <th>Tenant ID:</th>
                        <td><code><?= esc($tenant['tenant_id']) ?></code></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?= esc($tenant['email']) ?></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <?php if ($tenant['active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Subscription:</th>
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
                    </tr>
                    <tr>
                        <th>Created:</th>
                        <td><?= date('Y-m-d H:i', strtotime($tenant['created_at'])) ?></td>
                    </tr>
                    <?php if ($tenant['updated_at']): ?>
                        <tr>
                            <th>Last Updated:</th>
                            <td><?= date('Y-m-d H:i', strtotime($tenant['updated_at'])) ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-line me-1"></i>
                Usage Statistics
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="card-title">Total Requests</h6>
                                <h3><?= number_format($tenant['total_requests']) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="card-title">Total Tokens</h6>
                                <h3><?= number_format($tenant['total_tokens']) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-users me-1"></i>
                    API Users (<?= count($apiUsers) ?>)
                </div>
                <a href="<?= site_url('admin/tenants/users/' . $tenant['id']) ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-user-plus me-1"></i>Manage API Users
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($apiUsers)): ?>
                    <p class="text-muted text-center">No API users found for this tenant.</p>
                <?php else: ?>
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Name</th>
                                    <th>Quota</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($apiUsers as $user): ?>
                                    <tr>
                                        <td><code><?= esc($user['user_id']) ?></code></td>
                                        <td><?= esc($user['name']) ?></td>
                                        <td><?= number_format($user['quota']) ?></td>
                                        <td>
                                            <?php if ($user['active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-puzzle-piece me-1"></i>
                    Buttons
                </div>
                <a href="<?= site_url('admin/buttons/' . $tenant['id']) ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-external-link-alt me-1"></i>Manage Buttons
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($buttons)): ?>
                    <p class="text-muted text-center">No buttons configured for this tenant.</p>
                <?php else: ?>
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Domain</th>
                                    <th>Provider</th>
                                    <th>Model</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($buttons as $button): ?>
                                    <tr>
                                        <td><?= esc($button['name']) ?></td>
                                        <td><?= esc($button['domain']) ?></td>
                                        <td><span class="badge badge-provider"><?= esc($button['provider']) ?></span></td>
                                        <td><span class="badge badge-model"><?= esc($button['model']) ?></span></td>
                                        <td>
                                            <?php if ($button['active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
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

<?= $this->endSection() ?>