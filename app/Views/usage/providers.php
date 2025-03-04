<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-server me-1"></i>
                Provider Statistics
            </div>
            <div class="card-body">
                <?php if (empty($provider_stats)): ?>
                    <p class="text-muted text-center">No provider usage data available yet.</p>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Provider</th>
                                <th class="text-end">Requests</th>
                                <th class="text-end">Tokens</th>
                                <th class="text-end">Tenants</th>
                                <th class="text-end">Users</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($provider_stats as $stat): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-provider"><?= htmlspecialchars($stat->provider) ?></span>
                                    </td>
                                    <td class="text-end"><?= number_format($stat->request_count) ?></td>
                                    <td class="text-end"><?= number_format($stat->total_tokens) ?></td>
                                    <td class="text-end"><?= number_format($stat->tenant_count) ?></td>
                                    <td class="text-end"><?= number_format($stat->user_count) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-pie me-1"></i>
                Image Usage
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <?php if (empty($provider_stats)): ?>
                    <p class="text-muted">No image usage data available yet.</p>
                <?php else: ?>
                    <div class="text-center">
                        <div class="h3 mb-0"><?= isset($provider_stats[0]->image_requests) ? number_format(array_sum(array_column((array)$provider_stats, 'image_requests'))) : '0' ?></div>
                        <div class="text-muted">Total image requests</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-table me-1"></i>
                Models by Provider
            </div>
            <div class="card-body">
                <?php if (empty($model_stats)): ?>
                    <p class="text-muted text-center">No model usage data available yet.</p>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Provider</th>
                                <th>Model</th>
                                <th class="text-end">Requests</th>
                                <th class="text-end">Tokens</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($model_stats as $stat): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-provider"><?= htmlspecialchars($stat->provider) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-model"><?= htmlspecialchars($stat->model) ?></span>
                                    </td>
                                    <td class="text-end"><?= number_format($stat->request_count) ?></td>
                                    <td class="text-end"><?= number_format($stat->total_tokens) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>