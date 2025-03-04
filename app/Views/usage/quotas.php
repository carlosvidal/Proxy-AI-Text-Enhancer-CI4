<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-user-shield me-1"></i>
        User Quotas
    </div>
    <div class="card-body">
        <?php if (empty($quotas)): ?>
            <p class="text-muted text-center">No user quota data available yet.</p>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>User</th>
                        <th>Total Quota</th>
                        <th>Used Tokens</th>
                        <th>Remaining</th>
                        <th>Reset Period</th>
                        <th>Usage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quotas as $quota): ?>
                        <tr>
                            <td><?= htmlspecialchars($quota->tenant_id) ?></td>
                            <td><?= htmlspecialchars($quota->user_id) ?></td>
                            <td><?= number_format($quota->total_quota) ?></td>
                            <td><?= number_format($quota->used_tokens) ?></td>
                            <td><?= number_format($quota->remaining_quota) ?></td>
                            <td><?= ucfirst($quota->reset_period) ?></td>
                            <td>
                                <?php
                                $percentage = ($quota->total_quota > 0) ?
                                    min(100, ($quota->used_tokens / $quota->total_quota) * 100) : 0;
                                $class = $percentage > 90 ? 'danger' : ($percentage > 70 ? 'warning' : 'success');
                                ?>
                                <div class="progress">
                                    <div class="progress-bar bg-<?= $class ?>" role="progressbar"
                                        style="width: <?= $percentage ?>%;"
                                        aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?= round($percentage) ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>