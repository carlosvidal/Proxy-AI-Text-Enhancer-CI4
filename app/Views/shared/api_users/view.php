<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?= $title ?></h1>
    
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user me-1"></i>
                    API User Details
                </div>
                <div class="card-body">
                    <!-- Basic Info -->
                    <dl class="row mb-0">
                        <dt class="col-sm-3">User ID</dt>
                        <dd class="col-sm-9"><?= esc($user['user_id']) ?></dd>

                        <dt class="col-sm-3">External ID</dt>
                        <dd class="col-sm-9"><?= esc($user['external_id']) ?></dd>

                        <dt class="col-sm-3">Name</dt>
                        <dd class="col-sm-9"><?= esc($user['name']) ?></dd>

                        <dt class="col-sm-3">Email</dt>
                        <dd class="col-sm-9"><?= $user['email'] ? esc($user['email']) : '<em>Not set</em>' ?></dd>

                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9">
                            <?php if ($user['active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-3">Monthly Quota</dt>
                        <dd class="col-sm-9"><?= number_format($user['quota']) ?> tokens</dd>

                        <dt class="col-sm-3">Daily Quota</dt>
                        <dd class="col-sm-9"><?= number_format($user['daily_quota'] ?? 10000) ?> tokens</dd>

                        <dt class="col-sm-3">Created</dt>
                        <dd class="col-sm-9"><?= date('Y-m-d H:i:s', strtotime($user['created_at'])) ?></dd>

                        <dt class="col-sm-3">Last Updated</dt>
                        <dd class="col-sm-9"><?= date('Y-m-d H:i:s', strtotime($user['updated_at'])) ?></dd>

                        <dt class="col-sm-3">Last Activity</dt>
                        <dd class="col-sm-9">
                            <?php if ($user['last_activity']): ?>
                                <?= date('Y-m-d H:i:s', strtotime($user['last_activity'])) ?>
                            <?php else: ?>
                                <em>No activity yet</em>
                            <?php endif; ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- Usage Statistics -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Usage Statistics
                </div>
                <div class="card-body">
                    <!-- Total Usage -->
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Total Requests</dt>
                        <dd class="col-sm-6"><?= number_format($user['usage']['total_requests']) ?></dd>

                        <dt class="col-sm-6">Total Tokens</dt>
                        <dd class="col-sm-6"><?= number_format($user['usage']['total_tokens']) ?></dd>

                        <dt class="col-sm-6">Avg. Tokens/Request</dt>
                        <dd class="col-sm-6"><?= number_format($user['usage']['avg_tokens_per_request'], 1) ?></dd>
                    </dl>

                    <!-- Monthly Usage Chart -->
                    <div class="mt-4">
                        <h5>Monthly Usage</h5>
                        <canvas id="monthlyUsageChart" width="100%" height="30"></canvas>
                    </div>

                    <!-- Daily Usage Chart -->
                    <div class="mt-4">
                        <h5>Daily Usage</h5>
                        <canvas id="dailyUsageChart" width="100%" height="30"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->section('scripts') ?>
<script>
// Monthly Usage Chart
var monthlyCtx = document.getElementById('monthlyUsageChart');
new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($user['usage']['monthly_usage'])) ?>,
        datasets: [{
            label: 'Token Usage',
            data: <?= json_encode(array_values($user['usage']['monthly_usage'])) ?>,
            backgroundColor: 'rgba(0, 123, 255, 0.5)',
            borderColor: 'rgba(0, 123, 255, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Daily Usage Chart
var dailyCtx = document.getElementById('dailyUsageChart');
new Chart(dailyCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($user['usage']['daily_usage'])) ?>,
        datasets: [{
            label: 'Token Usage',
            data: <?= json_encode(array_values($user['usage']['daily_usage'])) ?>,
            backgroundColor: 'rgba(40, 167, 69, 0.5)',
            borderColor: 'rgba(40, 167, 69, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
<?php $this->endSection() ?>

<?php $this->endSection() ?>
