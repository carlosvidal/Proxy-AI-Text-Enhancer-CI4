<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('buttons') ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Buttons
        </a>
        <h2><?= esc($button['name']) ?> <span class="badge badge-tenant"><?= esc($button['domain']) ?></span></h2>
    </div>
    <div>
        <a href="<?= site_url('buttons/edit/' . $button['button_id']) ?>" class="btn btn-warning text-white">
            <i class="fas fa-edit me-1"></i>Edit
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-info-circle me-1"></i>
                Button Information
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th style="width: 30%">Name:</th>
                        <td><?= esc($button['name']) ?></td>
                    </tr>
                    <tr>
                        <th>Button ID:</th>
                        <td>
                            <code><?= esc($button['button_id']) ?></code>
                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('<?= esc($button['button_id']) ?>')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </td>
                    </tr>
                    <?php if (!empty($button['description'])): ?>
                    <tr>
                        <th>Description:</th>
                        <td><?= esc($button['description']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Domain:</th>
                        <td>
                            <a href="<?= esc($button['domain']) ?>" target="_blank" rel="noopener noreferrer">
                                <?= esc($button['domain']) ?>
                                <i class="fas fa-external-link-alt ms-1"></i>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th>Provider:</th>
                        <td><span class="badge badge-provider"><?= esc($providers[$button['provider']]) ?></span></td>
                    </tr>
                    <tr>
                        <th>Model:</th>
                        <td><span class="badge badge-model"><?= esc($button['model']) ?></span></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <?php if ($button['status'] === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Created:</th>
                        <td><?= date('Y-m-d H:i', strtotime($button['created_at'])) ?></td>
                    </tr>
                    <?php if ($button['updated_at']): ?>
                        <tr>
                            <th>Last Updated:</th>
                            <td><?= date('Y-m-d H:i', strtotime($button['updated_at'])) ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-comment-alt me-1"></i>
                System Prompt
            </div>
            <div class="card-body">
                <?php if (empty($button['prompt'])): ?>
                    <p class="text-muted text-center">No system prompt defined for this button.</p>
                <?php else: ?>
                    <pre class="prompt"><?= esc($button['prompt']) ?></pre>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-bar me-1"></i>
                Usage Statistics
            </div>
            <div class="card-body">
                <?php if (isset($button['usage'])): ?>
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Total Requests</dt>
                        <dd class="col-sm-6"><?= number_format($button['usage']['total_requests']) ?></dd>

                        <dt class="col-sm-6">Total Tokens</dt>
                        <dd class="col-sm-6"><?= number_format($button['usage']['total_tokens']) ?></dd>

                        <dt class="col-sm-6">Avg. Tokens/Request</dt>
                        <dd class="col-sm-6"><?= number_format($button['usage']['avg_tokens_per_request'], 1) ?></dd>

                        <dt class="col-sm-6">Max Tokens/Request</dt>
                        <dd class="col-sm-6"><?= number_format($button['usage']['max_tokens']) ?></dd>

                        <dt class="col-sm-6">Unique Users</dt>
                        <dd class="col-sm-6"><?= number_format($button['usage']['unique_users']) ?></dd>

                        <?php if ($button['last_used']): ?>
                            <dt class="col-sm-6">Last Used</dt>
                            <dd class="col-sm-6"><?= date('Y-m-d H:i', strtotime($button['last_used'])) ?></dd>
                        <?php endif; ?>
                    </dl>
                <?php else: ?>
                    <p class="text-muted text-center">No usage data available for this button.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-code me-1"></i>
        Integration Details
    </div>
    <div class="card-body">
        <h5>1. Add the Button Script</h5>
        <p>Add this script to your HTML page:</p>
        <pre class="integration-code"><code>&lt;script src="<?= site_url('assets/js/button.js') ?>"&gt;&lt;/script&gt;</code></pre>

        <h5 class="mt-4">2. Initialize the Button</h5>
        <p>Add this code to initialize the button:</p>
        <pre class="integration-code"><code>&lt;script&gt;
const btn = new AIButton({
    buttonId: '<?= esc($button['button_id']) ?>',
    selector: '#my-button', // Replace with your button's selector
    endpoint: '<?= site_url('api/enhance') ?>'
});
&lt;/script&gt;</code></pre>

        <h5 class="mt-4">3. Add the Button Element</h5>
        <p>Add this HTML where you want the button to appear:</p>
        <pre class="integration-code"><code>&lt;button id="my-button" class="ai-button"&gt;
    Enhance with AI
&lt;/button&gt;</code></pre>

        <div class="alert alert-info mt-4">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Need help?</strong> Check out our <a href="<?= site_url('docs/integration') ?>" class="alert-link">integration guide</a> for more details and examples.
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?php $this->section('styles') ?>
<style>
.integration-code {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.25rem;
    margin-bottom: 1rem;
}
.prompt {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.25rem;
    white-space: pre-wrap;
    font-family: monospace;
    margin-bottom: 0;
}
.badge-provider {
    background-color: #6f42c1;
    color: white;
}
.badge-model {
    background-color: #0dcaf0;
    color: white;
}
.badge-tenant {
    background-color: #198754;
    color: white;
}
</style>
<?= $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Could add a toast notification here
        console.log('Copied to clipboard');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}
</script>
<?= $this->endSection() ?>