<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-list-alt me-1"></i>
        Usage Logs
    </div>
    <div class="card-body">
        <?php if (empty($logs)): ?>
            <p class="text-muted text-center">No usage logs available yet.</p>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Tenant</th>
                        <th>User</th>
                        <th>Provider</th>
                        <th>Model</th>
                        <th>Tokens</th>
                        <th>Image</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= date('Y-m-d H:i:s', strtotime($log->usage_date)) ?></td>
                            <td><?= htmlspecialchars($log->tenant_id) ?></td>
                            <td><?= htmlspecialchars($log->user_id) ?></td>
                            <td>
                                <span class="badge badge-provider"><?= htmlspecialchars($log->provider) ?></span>
                            </td>
                            <td>
                                <span class="badge badge-model"><?= htmlspecialchars($log->model) ?></span>
                            </td>
                            <td><?= number_format($log->tokens) ?></td>
                            <td>
                                <?php if ($log->has_image): ?>
                                    <span class="badge bg-info">Yes</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">No</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 0; $i < $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= site_url('usage/logs/' . $i) ?>">
                                    <?= $i + 1 ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>