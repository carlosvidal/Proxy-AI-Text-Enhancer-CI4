<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('admin/tenants/users/' . $tenant['tenant_id']) ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to API Users
        </a>
        <h2>API Usage for <?= esc($user['name'] ?? $user['external_id'] ?? $user['user_id']) ?></h2>
    </div>
</div>

<div class="row">
    <!-- API User Info Card -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user me-1"></i>
                API User Information
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">User ID</dt>
                    <dd class="col-sm-8"><code><?= esc($user['user_id']) ?></code></dd>

                    <dt class="col-sm-4">External ID</dt>
                    <dd class="col-sm-8"><code><?= esc($user['external_id'] ?? 'N/A') ?></code></dd>

                    <dt class="col-sm-4">Name</dt>
                    <dd class="col-sm-8"><?= esc($user['name'] ?? 'N/A') ?></dd>

                    <?php if (!empty($user['email'])): ?>
                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8"><?= esc($user['email']) ?></dd>
                    <?php else: ?>
                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">N/A</dd>
                    <?php endif; ?>

                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <?php if ($user['active']): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4">Quota</dt>
                    <dd class="col-sm-8"><?= number_format($user['quota']) ?> tokens</dd>

                    <dt class="col-sm-4">Created</dt>
                    <dd class="col-sm-8"><?= date('M j, Y', strtotime($user['created_at'])) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Quota Information -->
    <div class="col-md-8">
        <!-- Current Usage Cards -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-calendar-alt me-1"></i>
                        Monthly Usage
                    </div>
                    <div class="card-body">
                        <h4 class="card-title"><?= number_format($quotaInfo['monthly_used']) ?> / <?= number_format($quotaInfo['monthly_quota']) ?></h4>
                        <div class="progress mb-2">
                            <div class="progress-bar" role="progressbar" style="width: <?= $quotaInfo['monthly_percentage'] ?>%" 
                                 aria-valuenow="<?= $quotaInfo['monthly_percentage'] ?>" aria-valuemin="0" aria-valuemax="100">
                                <?= $quotaInfo['monthly_percentage'] ?>%
                            </div>
                        </div>
                        <small class="text-muted">
                            <strong><?= number_format($quotaInfo['monthly_remaining']) ?> tokens remaining</strong><br>
                            Resets on <?= date('M 1, Y', strtotime('+1 month')) ?>
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-calendar-day me-1"></i>
                        Daily Usage
                    </div>
                    <div class="card-body">
                        <h4 class="card-title"><?= number_format($quotaInfo['daily_used']) ?> / <?= number_format($quotaInfo['daily_quota']) ?></h4>
                        <div class="progress mb-2">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $quotaInfo['daily_percentage'] ?>%" 
                                 aria-valuenow="<?= $quotaInfo['daily_percentage'] ?>" aria-valuemin="0" aria-valuemax="100">
                                <?= $quotaInfo['daily_percentage'] ?>%
                            </div>
                        </div>
                        <small class="text-muted">
                            <strong><?= number_format($quotaInfo['daily_remaining']) ?> tokens remaining</strong><br>
                            Resets tomorrow at midnight
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Usage Chart -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-line me-1"></i>
                Monthly Usage Statistics
            </div>
            <div class="card-body">
                <canvas id="monthlyUsageChart" width="100%" height="40"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Usage Table -->
<div class="card">
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
                        <th>Total Requests</th>
                        <th>Total Tokens</th>
                        <th>Average Tokens/Request</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthlyUsage as $usage): ?>
                        <tr>
                            <td><?= date('F Y', strtotime($usage['month'] . '-01')) ?></td>
                            <td><?= number_format($usage['total_requests']) ?></td>
                            <td><?= number_format($usage['total_tokens']) ?></td>
                            <td>
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
    
    const months = <?= json_encode(array_map(function($usage) {
        return date('M Y', strtotime($usage['month'] . '-01'));
    }, $monthlyUsage)) ?>;
    
    const requests = <?= json_encode(array_map(function($usage) {
        return $usage['total_requests'];
    }, $monthlyUsage)) ?>;
    
    const tokens = <?= json_encode(array_map(function($usage) {
        return $usage['total_tokens'];
    }, $monthlyUsage)) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Requests',
                    data: requests,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                    yAxisID: 'requests'
                },
                {
                    label: 'Tokens',
                    data: tokens,
                    borderColor: 'rgb(255, 99, 132)',
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
