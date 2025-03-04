<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php if (!$tables_exist['usage_logs'] || !$tables_exist['user_quotas']): ?>
    <div class="alert alert-warning" role="alert">
        <h4 class="alert-heading"><i class="fas fa-exclamation-circle me-2"></i> Database Setup Required</h4>
        <p>The database tables for the LLM Proxy have not been created yet.</p>
        <hr>
        <p class="mb-0">
            Please run the migrations to set up the database.
            <a href="<?= site_url('migrate') ?>" class="btn btn-sm btn-primary">Run Migrations</a>
        </p>
    </div>
<?php else: ?>

    <!-- Stats Cards -->
    <div class="row">
        <div class="col-md-4 col-sm-6">
            <div class="card stat-card mb-4">
                <div class="stat-icon">
                    <i class="fas fa-paper-plane text-primary"></i>
                </div>
                <div class="stat-number"><?= number_format($stats['total_requests']) ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card stat-card mb-4">
                <div class="stat-icon">
                    <i class="fas fa-bolt text-warning"></i>
                </div>
                <div class="stat-number"><?= number_format($stats['recent_requests']) ?></div>
                <div class="stat-label">Requests (Last 24h)</div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card stat-card mb-4">
                <div class="stat-icon">
                    <i class="fas fa-coins text-success"></i>
                </div>
                <div class="stat-number"><?= number_format($stats['total_tokens']) ?></div>
                <div class="stat-label">Total Tokens</div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card stat-card mb-4">
                <div class="stat-icon">
                    <i class="fas fa-building text-info"></i>
                </div>
                <div class="stat-number"><?= number_format($stats['unique_tenants']) ?></div>
                <div class="stat-label">Unique Tenants</div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card stat-card mb-4">
                <div class="stat-icon">
                    <i class="fas fa-users text-primary"></i>
                </div>
                <div class="stat-number"><?= number_format($stats['unique_users']) ?></div>
                <div class="stat-label">Unique Users</div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card stat-card mb-4">
                <div class="stat-icon">
                    <i class="fas fa-image text-danger"></i>
                </div>
                <div class="stat-number"><?= number_format($stats['image_requests']) ?></div>
                <div class="stat-label">Image Requests</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Usage Over Time Chart -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-line me-1"></i>
                    Usage Over Time (Last 30 Days)
                </div>
                <div class="card-body">
                    <canvas id="usageChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Provider Distribution -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Provider Distribution
                </div>
                <div class="card-body">
                    <canvas id="providerChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Models -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-list-ol me-1"></i>
                    Top Models
                </div>
                <div class="card-body">
                    <?php if (empty($charts_data['usage_by_model'])): ?>
                        <p class="text-muted text-center">No model usage data available.</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Model</th>
                                    <th class="text-end">Requests</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($charts_data['usage_by_model'] as $model): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-model"><?= htmlspecialchars($model->model) ?></span>
                                        </td>
                                        <td class="text-end"><?= number_format($model->count) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Database Status -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-database me-1"></i>
                    Database Status
                </div>
                <div class="card-body">
                    <table class="table">
                        <tbody>
                            <tr>
                                <td><strong>user_quotas</strong></td>
                                <td>
                                    <?php if ($tables_exist['user_quotas']): ?>
                                        <span class="badge bg-success">Exists</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Missing</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>usage_logs</strong></td>
                                <td>
                                    <?php if ($tables_exist['usage_logs']): ?>
                                        <span class="badge bg-success">Exists</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Missing</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>llm_cache</strong></td>
                                <td>
                                    <?php if ($tables_exist['llm_cache']): ?>
                                        <span class="badge bg-success">Exists</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Missing</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Initialization Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Usage Over Time Chart
            const usageCtx = document.getElementById('usageChart').getContext('2d');

            // Parse chart data
            const usageData = <?= json_encode($charts_data['usage_by_date'] ?? []) ?>;
            const labels = usageData.map(item => item.date);
            const requestsData = usageData.map(item => item.requests);
            const tokensData = usageData.map(item => item.tokens);

            new Chart(usageCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Requests',
                            data: requestsData,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            tension: 0.2,
                            fill: true,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Tokens',
                            data: tokensData,
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
                            borderWidth: 2,
                            tension: 0.2,
                            fill: true,
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
                                text: 'Requests'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Tokens'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });

            // Provider Distribution Chart
            const providerCtx = document.getElementById('providerChart').getContext('2d');

            // Parse provider chart data
            const providerData = <?= json_encode($charts_data['usage_by_provider'] ?? []) ?>;
            const providerLabels = providerData.map(item => item.provider);
            const providerCounts = providerData.map(item => item.count);

            // Color palette for providers
            const providerColors = [
                '#3b82f6', // blue
                '#10b981', // green
                '#8b5cf6', // purple
                '#f59e0b', // amber
                '#ef4444', // red
                '#06b6d4', // cyan
                '#ec4899' // pink
            ];

            new Chart(providerCtx, {
                type: 'doughnut',
                data: {
                    labels: providerLabels,
                    datasets: [{
                        data: providerCounts,
                        backgroundColor: providerColors.slice(0, providerLabels.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        });
    </script>
<?php endif; ?>

<?= $this->endSection() ?>