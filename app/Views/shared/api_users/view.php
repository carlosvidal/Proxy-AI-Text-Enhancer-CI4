<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>API User Details</h2>
        <p class="text-muted">View API user information and usage statistics</p>
    </div>
    <div>
        <a href="<?= site_url('api-users') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to API Users
        </a>
    </div>
</div>

<div class="row">
    <!-- API User Information -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-user me-1"></i>
                User Information
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Internal ID</dt>
                    <dd class="col-sm-8">
                        <code><?= esc($user['user_id']) ?></code>
                        <button class="btn btn-sm btn-outline-secondary ms-2" 
                                onclick="copyToClipboard('<?= esc($user['user_id']) ?>')"
                                title="Copy to clipboard">
                            <i class="fas fa-copy"></i>
                        </button>
                    </dd>

                    <dt class="col-sm-4">External ID</dt>
                    <dd class="col-sm-8">
                        <?php if ($user['external_id']): ?>
                            <code><?= esc($user['external_id']) ?></code>
                            <button class="btn btn-sm btn-outline-secondary ms-2" 
                                    onclick="copyToClipboard('<?= esc($user['external_id']) ?>')"
                                    title="Copy to clipboard">
                                <i class="fas fa-copy"></i>
                            </button>
                        <?php else: ?>
                            <span class="text-muted">Not set</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4">Name</dt>
                    <dd class="col-sm-8"><?= esc($user['name']) ?: '<span class="text-muted">Not set</span>' ?></dd>

                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8"><?= esc($user['email']) ?: '<span class="text-muted">Not set</span>' ?></dd>

                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <?php if ($user['active']): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4">Created</dt>
                    <dd class="col-sm-8"><?= date('Y-m-d H:i', strtotime($user['created_at'])) ?></dd>

                    <dt class="col-sm-4">Last Activity</dt>
                    <dd class="col-sm-8">
                        <?php if ($user['last_activity']): ?>
                            <?= date('Y-m-d H:i', strtotime($user['last_activity'])) ?>
                        <?php else: ?>
                            <span class="text-muted">Never</span>
                        <?php endif; ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Usage Statistics -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-chart-line me-1"></i>
                Usage Statistics
            </div>
            <div class="card-body">
                <!-- Monthly Usage -->
                <div class="mb-4">
                    <h6 class="card-subtitle mb-2">Monthly Token Usage</h6>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span><?= number_format($user['monthly_usage']) ?> / <?= number_format($user['quota']) ?> tokens</span>
                        <span class="text-muted"><?= round(($user['monthly_usage'] / $user['quota']) * 100) ?>%</span>
                    </div>
                    <?php 
                    $usagePercent = ($user['monthly_usage'] / $user['quota']) * 100;
                    $barClass = $usagePercent >= 90 ? 'bg-danger' : ($usagePercent >= 75 ? 'bg-warning' : 'bg-success');
                    ?>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar <?= $barClass ?>" 
                             role="progressbar" 
                             style="width: <?= min($usagePercent, 100) ?>%">
                        </div>
                    </div>
                    <?php if ($user['monthly_usage'] >= $user['quota']): ?>
                        <div class="text-danger mt-1">
                            <small><i class="fas fa-exclamation-triangle me-1"></i>Quota exceeded</small>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Total Usage -->
                <dl class="row mb-0">
                    <dt class="col-sm-6">Total Requests</dt>
                    <dd class="col-sm-6"><?= number_format($user['usage']['total_requests']) ?></dd>

                    <dt class="col-sm-6">Total Tokens</dt>
                    <dd class="col-sm-6"><?= number_format($user['usage']['total_tokens']) ?></dd>

                    <dt class="col-sm-6">Avg. Tokens/Request</dt>
                    <dd class="col-sm-6"><?= number_format($user['usage']['avg_tokens_per_request'], 1) ?></dd>

                    <dt class="col-sm-6">Max Tokens/Request</dt>
                    <dd class="col-sm-6"><?= number_format($user['usage']['max_tokens']) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Button Access -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-plug me-1"></i>
                Button Access
            </div>
            <div class="card-body">
                <?php if (empty($user['buttons'])): ?>
                    <div class="alert alert-info mb-0">
                        No buttons assigned to this API user.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Button ID</th>
                                    <th>Name</th>
                                    <th>Domain</th>
                                    <th>Access Granted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user['buttons'] as $button): ?>
                                    <tr>
                                        <td><code><?= esc($button['button_id']) ?></code></td>
                                        <td><?= esc($button['name']) ?></td>
                                        <td><?= esc($button['domain']) ?></td>
                                        <td><?= date('Y-m-d H:i', strtotime($button['access_granted_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show a brief visual feedback
        const btn = event.currentTarget;
        const icon = btn.querySelector('i');
        const originalClass = icon.className;
        
        icon.className = 'fas fa-check';
        btn.classList.add('btn-success');
        btn.classList.remove('btn-outline-secondary');
        
        setTimeout(() => {
            icon.className = originalClass;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-secondary');
        }, 1000);
    });
}
</script>

<?= $this->endSection() ?>
