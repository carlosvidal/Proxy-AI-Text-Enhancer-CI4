<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('admin/tenants') ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Tenants
        </a>
        <h2><?= esc($tenant['name']) ?></h2>
        <p class="text-muted mb-0">View tenant details and usage statistics</p>
    </div>
    <div>
        <a href="<?= site_url('admin/tenants/edit/' . $tenant['tenant_id']) ?>" class="btn btn-primary">
            <i class="fas fa-edit me-1"></i>Edit Tenant
        </a>
    </div>
</div>

<div class="row">
    <!-- Tenant Information -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-building me-1"></i>
                Tenant Information
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Name</dt>
                    <dd class="col-sm-8"><?= esc($tenant['name']) ?></dd>

                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8"><?= esc($tenant['email'] ?? 'N/A') ?></dd>

                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <?php if ($tenant['active']): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4">Subscription</dt>
                    <dd class="col-sm-8">
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
                    </dd>

                    <dt class="col-sm-4">Created</dt>
                    <dd class="col-sm-8"><?= date('M j, Y', strtotime($tenant['created_at'])) ?></dd>
                </dl>
            </div>
        </div>

        <!-- API Users Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-users me-1"></i>
                API Users
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="h5 mb-0"><?= $tenant['api_users'] ?> API Users</h3>
                        <small class="text-muted">Manage API access and quotas</small>
                    </div>
                    <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/users') ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-users me-1"></i>Manage Users
                    </a>
                </div>

                <?php if (!empty($apiUsers)): ?>
                <div class="mt-3">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Usage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($apiUsers as $user): ?>
                                <tr>
                                    <td><small><code><?= esc($user['user_id']) ?></code></small></td>
                                    <td><small><?= number_format($user['usage']) ?> tokens</small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Buttons Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-code me-1"></i>
                Buttons
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="h5 mb-0"><?= count($buttons) ?> Buttons</h3>
                        <small class="text-muted">Manage buttons and usage statistics</small>
                    </div>
                    <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/buttons') ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-code me-1"></i>Manage Buttons
                    </a>
                </div>

                <?php if (!empty($buttons)): ?>
                <div class="mt-3">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Requests</th>
                                    <th>Tokens</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($buttons as $button): ?>
                                <tr>
                                    <td><small><code><?= esc($button['name']) ?></code></small></td>
                                    <td><small><?= number_format($button['usage']['total_requests'] ?? 0) ?></small></td>
                                    <td><small><?= number_format($button['usage']['total_tokens'] ?? 0) ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Usage Statistics -->
    <div class="col-md-8">
        <!-- Usage Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-pie me-1"></i>
                Usage Summary
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center">
                            <h3 class="mb-0"><?= number_format($tenant['total_buttons'] ?? 0) ?></h3>
                            <small class="text-muted">Total Buttons</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h3 class="mb-0"><?= number_format($tenant['total_requests'] ?? 0) ?></h3>
                            <small class="text-muted">Total Requests</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h3 class="mb-0"><?= number_format($tenant['total_tokens'] ?? 0) ?></h3>
                            <small class="text-muted">Total Tokens</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Usage Chart -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-line me-1"></i>
                Monthly Usage Statistics
            </div>
            <div class="card-body">
                <canvas id="monthlyUsageChart" width="100%" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Buttons List -->
<div class="card mt-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-code me-1"></i>
                Buttons
            </div>
            <div>
                <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/buttons') ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i>Manage Buttons
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($buttons)): ?>
            <div class="text-center py-4">
                <i class="fas fa-code fa-3x text-muted mb-3"></i>
                <p class="text-muted">No buttons found for this tenant.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Requests</th>
                            <th>Tokens</th>
                            <th>Avg Tokens/Request</th>
                            <th>Max Tokens</th>
                            <th>Unique Users</th>
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
                                        <a href="<?= site_url('admin/buttons/edit/' . $button['button_id']) ?>" 
                                           class="btn btn-sm btn-warning text-white" 
                                           title="Edit Button">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= site_url('admin/buttons/delete/' . $button['button_id']) ?>" 
                                           class="btn btn-sm btn-danger" 
                                           title="Delete Button"
                                           onclick="return confirm('Are you sure you want to delete this button?')">
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

<!-- Monthly Usage Table -->
<div class="card mt-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        Monthly Usage Details
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th class="text-end">Total Requests</th>
                        <th class="text-end">Total Tokens</th>
                        <th class="text-end">Average Tokens/Request</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthlyUsage ?? [] as $usage): ?>
                        <?php 
                        // Parse SQLite date format (YYYY-MM)
                        $month = $usage['month'];
                        $monthDisplay = date('F Y', strtotime($month . '-01'));
                        ?>
                        <tr>
                            <td><?= $monthDisplay ?></td>
                            <td class="text-end"><?= number_format($usage['total_requests']) ?></td>
                            <td class="text-end"><?= number_format($usage['total_tokens']) ?></td>
                            <td class="text-end">
                                <?= $usage['total_requests'] > 0 
                                    ? number_format($usage['total_tokens'] / $usage['total_requests'], 1) 
                                    : '0' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($monthlyUsage)): ?>
                        <tr>
                            <td colspan="4" class="text-center">No usage data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js initialization -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('monthlyUsageChart');
    
    // Parse SQLite date format (YYYY-MM)
    const months = <?= json_encode(array_map(function($usage) {
        return date('M Y', strtotime($usage['month'] . '-01'));
    }, $monthlyUsage ?? [])) ?>;
    
    const requests = <?= json_encode(array_map(function($usage) {
        return (int)$usage['total_requests'];
    }, $monthlyUsage ?? [])) ?>;
    
    const tokens = <?= json_encode(array_map(function($usage) {
        return (int)$usage['total_tokens'];
    }, $monthlyUsage ?? [])) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Requests',
                    data: requests,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    borderWidth: 2,
                    tension: 0.1,
                    yAxisID: 'requests'
                },
                {
                    label: 'Tokens',
                    data: tokens,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    borderWidth: 2,
                    tension: 0.1,
                    yAxisID: 'tokens'
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                requests: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Total Requests'
                    }
                },
                tokens: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Total Tokens'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
});
</script>

<?= $this->endSection() ?>
