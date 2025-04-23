<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4">
    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1><?= $title ?></h1>
                <div>
                    <a href="/usage/api" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to API Users
                    </a>
                    <a href="/users/edit/<?= $user->user_id ?>" class="btn btn-primary ms-2">
                        <i class="fas fa-edit me-1"></i>
                        Edit User
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- User Info Cards -->
    <div class="row mt-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h4><?= number_format($user->quota) ?></h4>
                    <div>Token Quota</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card <?= $percentage > 90 ? 'bg-danger' : ($percentage > 70 ? 'bg-warning' : 'bg-success') ?> text-white mb-4">
                <div class="card-body">
                    <h4><?= number_format($user->total_tokens) ?></h4>
                    <div>Tokens Used (<?= number_format($percentage, 1) ?>%)</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h4><?= number_format($user->request_count) ?></h4>
                    <div>Total Requests</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card <?= $user->active ? 'bg-success' : 'bg-danger' ?> text-white mb-4">
                <div class="card-body">
                    <h4><?= $user->active ? 'Active' : 'Inactive' ?></h4>
                    <div>Current Status</div>
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
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for the chart
    const dates = <?= json_encode(array_column($daily_stats, 'usage_date')) ?>;
    const tokens = <?= json_encode(array_column($daily_stats, 'total_tokens')) ?>;
    const requests = <?= json_encode(array_column($daily_stats, 'request_count')) ?>;

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
