<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">API Usage This Month</h5>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="mb-0"><?= $current_usage ?> / <?= $quota ?></h2>
                        <small class="text-muted">Requests Used</small>
                    </div>
                    <div class="text-end">
                        <div class="progress" style="width: 100px; height: 100px;">
                            <canvas id="usageChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Usage History</h5>
                <canvas id="historyChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Recent Activity</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Button</th>
                                <th>Input Length</th>
                                <th>Output Length</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activity as $activity): ?>
                            <tr>
                                <td><?= $activity['created_at'] ?></td>
                                <td><?= $activity['button_name'] ?></td>
                                <td><?= $activity['input_length'] ?></td>
                                <td><?= $activity['output_length'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $activity['status'] === 'success' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($activity['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Usage Doughnut Chart
    const usageCtx = document.getElementById('usageChart').getContext('2d');
    new Chart(usageCtx, {
        type: 'doughnut',
        data: {
            labels: ['Used', 'Remaining'],
            datasets: [{
                data: [<?= $current_usage ?>, <?= $quota - $current_usage ?>],
                backgroundColor: ['#3b82f6', '#e5e7eb'],
                borderWidth: 0
            }]
        },
        options: {
            cutout: '70%',
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Usage History Line Chart
    const historyCtx = document.getElementById('historyChart').getContext('2d');
    new Chart(historyCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($usage_history, 'date')) ?>,
            datasets: [{
                label: 'Daily Usage',
                data: <?= json_encode(array_column($usage_history, 'count')) ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
});
</script>

<?= $this->endSection() ?>
