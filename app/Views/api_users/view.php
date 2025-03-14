<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('api-users') ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to API Users
        </a>
        <h2>API User Details</h2>
    </div>
    <div>
        <a href="<?= site_url('api-users/edit/' . $user['user_id']) ?>" class="btn btn-warning">
            <i class="fas fa-edit me-1"></i>Edit User
        </a>
    </div>
</div>

<div class="row">
    <!-- User Details -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user me-1"></i>
                User Information
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Name</dt>
                    <dd class="col-sm-8"><?= esc($user['name']) ?></dd>

                    <dt class="col-sm-4">User ID</dt>
                    <dd class="col-sm-8"><code><?= esc($user['user_id']) ?></code></dd>

                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8"><?= esc($user['email']) ?></dd>

                    <dt class="col-sm-4">Role</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-info"><?= ucfirst($user['role']) ?></span>
                    </dd>

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
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-line me-1"></i>
                Usage Statistics
            </div>
            <div class="card-body">
                <?php if (!empty($user['usage'])): ?>
                    <dl class="row">
                        <dt class="col-sm-6">Total Requests</dt>
                        <dd class="col-sm-6"><?= number_format($user['usage']['total_requests']) ?></dd>

                        <dt class="col-sm-6">Total Tokens</dt>
                        <dd class="col-sm-6"><?= number_format($user['usage']['total_tokens']) ?></dd>

                        <dt class="col-sm-6">Avg. Tokens/Request</dt>
                        <dd class="col-sm-6"><?= number_format($user['usage']['avg_tokens_per_request'], 2) ?></dd>

                        <dt class="col-sm-6">Max Tokens/Request</dt>
                        <dd class="col-sm-6"><?= number_format($user['usage']['max_tokens']) ?></dd>
                    </dl>
                <?php else: ?>
                    <p class="text-muted text-center mb-0">No usage data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Button Access -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-puzzle-piece me-1"></i>
        Button Access
    </div>
    <div class="card-body">
        <?php if (empty($buttonAccess)): ?>
            <p class="text-muted text-center mb-0">No buttons assigned to this user.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Button Name</th>
                            <th>Button ID</th>
                            <th>Domain</th>
                            <th>Provider</th>
                            <th>Model</th>
                            <th>Access Granted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($buttonAccess as $button): ?>
                            <tr>
                                <td><?= esc($button['name']) ?></td>
                                <td><code><?= esc($button['button_id']) ?></code></td>
                                <td><?= esc($button['domain']) ?></td>
                                <td>
                                    <span class="badge badge-provider"><?= esc($button['provider']) ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-model"><?= esc($button['model']) ?></span>
                                </td>
                                <td><?= date('Y-m-d H:i', strtotime($button['access_granted_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
