<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-building fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-title mb-0">Total Tenants</h6>
                        <h2 class="mt-2 mb-0"><?= $stats['total_tenants'] ?></h2>
                        <small class="text-muted"><?= $stats['active_tenants'] ?> active</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-users fa-2x text-success"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-title mb-0">Total API Users</h6>
                        <h2 class="mt-2 mb-0"><?= $stats['total_api_users'] ?></h2>
                        <small class="text-muted">Across all tenants</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-sync fa-2x text-warning"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-title mb-0">Total Requests</h6>
                        <h2 class="mt-2 mb-0"><?= number_format($stats['total_requests']) ?></h2>
                        <small class="text-muted">All time</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-chart-line fa-2x text-info"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-title mb-0">Avg. Requests/Tenant</h6>
                        <h2 class="mt-2 mb-0">
                            <?= $stats['total_tenants'] ? number_format($stats['total_requests'] / $stats['total_tenants']) : 0 ?>
                        </h2>
                        <small class="text-muted">Per tenant average</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Usage by Subscription Status</h5>
                <canvas id="usageByStatusChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Subscription Distribution</h5>
                <canvas id="statusDistributionChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Tenants and Status Stats -->
<div class="row">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title mb-0">Recent Tenants</h5>
                    <a href="<?= site_url('admin/tenants') ?>" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>API Users</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTenants as $tenant): ?>
                            <tr>
                                <td>
                                    <a href="<?= site_url('admin/tenants/view/' . $tenant['tenant_id']) ?>" class="text-decoration-none">
                                        <?= esc($tenant['name']) ?>
                                    </a>
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
                                        <?= esc($tenant['subscription_status'] ?? 'Trial') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?= $tenant['api_users'] ?? 0 ?> users</span>
                                </td>
                                <td><?= date('M j, Y', strtotime($tenant['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-4">Subscription Statistics</h5>
                <?php foreach ($usageByStatus as $status): ?>
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><?= esc(ucfirst($status['subscription_status'] ?? 'Trial')) ?></h6>
                        <span class="badge bg-primary"><?= $status['tenant_count'] ?> tenants</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?= ($stats['total_tenants'] > 0) ? ($status['tenant_count'] / $stats['total_tenants'] * 100) : 0 ?>%" 
                             aria-valuenow="<?= $status['tenant_count'] ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="<?= $stats['total_tenants'] ?>">
                        </div>
                    </div>
                    <small class="text-muted">
                        <?= number_format($status['total_requests']) ?> total requests
                    </small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Usage by Status Chart
    const usageCtx = document.getElementById('usageByStatusChart').getContext('2d');
    new Chart(usageCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map(fn($s) => ucfirst($s['subscription_status'] ?? 'Trial'), $usageByStatus)) ?>,
            datasets: [{
                label: 'Total Requests',
                data: <?= json_encode(array_column($usageByStatus, 'total_requests')) ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Status Distribution Chart
    const statusCtx = document.getElementById('statusDistributionChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_map(fn($s) => ucfirst($s['subscription_status'] ?? 'Trial'), $usageByStatus)) ?>,
            datasets: [{
                data: <?= json_encode(array_column($usageByStatus, 'tenant_count')) ?>,
                backgroundColor: [
                    'rgba(59, 130, 246, 0.5)',
                    'rgba(16, 185, 129, 0.5)',
                    'rgba(245, 158, 11, 0.5)',
                    'rgba(239, 68, 68, 0.5)'
                ],
                borderColor: [
                    'rgb(59, 130, 246)',
                    'rgb(16, 185, 129)',
                    'rgb(245, 158, 11)',
                    'rgb(239, 68, 68)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>

<?= $this->endSection() ?>
