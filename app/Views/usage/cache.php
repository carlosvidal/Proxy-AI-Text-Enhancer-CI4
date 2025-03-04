<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-memory me-1"></i>
                Cache Summary
            </div>
            <div class="card-body">
                <div class="stat-card text-center mb-3">
                    <div class="stat-icon">
                        <i class="fas fa-hdd text-primary"></i>
                    </div>
                    <div class="stat-number"><?= number_format($cache_size) ?></div>
                    <div class="stat-label">Cached Responses</div>
                </div>

                <?php if (!empty($provider_stats)): ?>
                    <div class="mt-4">
                        <h6 class="text-muted">Cache by Provider</h6>
                        <ul class="list-group">
                            <?php foreach ($provider_stats as $stat): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($stat->provider) ?>
                                    <span class="badge bg-primary rounded-pill"><?= number_format($stat->entry_count) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-clock me-1"></i>
                Recent Cache Entries
            </div>
            <div class="card-body">
                <?php if (empty($recent_entries)): ?>
                    <p class="text-muted text-center">No cache entries available yet.</p>
                <?php else: ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Provider</th>
                                <th>Model</th>
                                <th>Hash</th>
                                <th>Size</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_entries as $entry): ?>
                                <tr>
                                    <td><?= date('Y-m-d H:i:s', strtotime($entry->created_at)) ?></td>
                                    <td>
                                        <span class="badge badge-provider"><?= htmlspecialchars($entry->provider) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-model"><?= htmlspecialchars($entry->model) ?></span>
                                    </td>
                                    <td>
                                        <code><?= substr($entry->prompt_hash, 0, 10) ?>...</code>
                                    </td>
                                    <td><?= round(strlen($entry->response) / 1024, 1) ?> KB</td>
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