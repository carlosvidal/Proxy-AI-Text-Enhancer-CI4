<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4">
    <h1 class="mt-4"><?= $title ?></h1>
    
    <!-- Summary Cards -->
    <div class="row mt-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h4><?= number_format($stats['total_tokens'] ?? 0) ?></h4>
                    <div>Total Tokens Used</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h4><?= number_format($stats['total_requests'] ?? 0) ?></h4>
                    <div>Total Requests</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Usage Chart -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-area me-1"></i>
                    Daily Usage (Last 30 Days)
                </div>
                <div class="card-body">
                    <canvas id="dailyUsageChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- API Users Table -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-users me-1"></i>
                    API Users
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>External ID</th>
                                <th>Quota</th>
                                <th>Usage</th>
                                <th>Requests</th>
                                <th>% Used</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($api_stats as $user): ?>
                            <tr>
                                <td><?= esc($user->id) ?></td>
                                <td><?= esc($user->external_id) ?></td>
                                <td><?= number_format($user->quota) ?></td>
                                <td><?= number_format($user->total_tokens) ?></td>
                                <td><?= number_format($user->request_count) ?></td>
                                <td>
                                    <?php $percentage = ($user->total_tokens / $user->quota) * 100; ?>
                                    <div class="progress">
                                        <div class="progress-bar <?= $percentage > 90 ? 'bg-danger' : ($percentage > 70 ? 'bg-warning' : 'bg-success') ?>" 
                                             role="progressbar" 
                                             style="width: <?= min(100, $percentage) ?>%"
                                             aria-valuenow="<?= $percentage ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?= number_format($percentage, 1) ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Button Usage Table -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-table me-1"></i>
                    Button Usage Statistics
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Button</th>
                                <th>Uses</th>
                                <th>Total Tokens</th>
                                <th>Avg. Tokens/Use</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($button_stats as $stat): ?>
                            <tr>
                                <td><?= esc($stat->button_name) ?></td>
                                <td><?= number_format($stat->use_count) ?></td>
                                <td><?= number_format($stat->total_tokens) ?></td>
                                <td><?= number_format($stat->use_count > 0 ? $stat->total_tokens / $stat->use_count : 0) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for the chart
    const dates = <?= json_encode(array_column($daily_stats, 'usage_date')) ?>;
    const tokens = <?= json_encode(array_column($daily_stats, 'daily_tokens')) ?>;
    const requests = <?= json_encode(array_column($daily_stats, 'daily_requests')) ?>;

    // Create the chart
    const ctx = document.getElementById('dailyUsageChart');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [
                {
                    label: 'Tokens',
                    data: tokens,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                    yAxisID: 'y'
                },
                {
                    label: 'Requests',
                    data: requests,
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1,
                    yAxisID: 'y1'
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
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Tokens'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Requests'
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