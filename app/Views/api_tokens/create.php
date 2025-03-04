<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('api/tokens') ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Tokens
        </a>
        <h2>Create API Token</h2>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-key me-1"></i>
        New API Token
    </div>
    <div class="card-body">
        <form action="<?= site_url('api/tokens/store') ?>" method="post">
            <?= csrf_field() ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger">
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="name" class="form-label">Token Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= set_value('name') ?>" required>
                <div class="form-text">Give your token a descriptive name to remember its purpose</div>
            </div>

            <?php if (session()->get('role') === 'admin' && isset($tenants) && !empty($tenants)): ?>
                <div class="mb-3">
                    <label for="tenant_id" class="form-label">Tenant Access</label>
                    <select class="form-select" id="tenant_id" name="tenant_id">
                        <option value="">All Tenants (Admin)</option>
                        <?php foreach ($tenants as $tenant): ?>
                            <option value="<?= $tenant['id'] ?>" <?= set_select('tenant_id', $tenant['id']) ?>>
                                <?= esc($tenant['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Limit this token to a specific tenant, or leave blank for all tenants</div>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="expires" class="form-label">Token Expiration</label>
                <select class="form-select" id="expires" name="expires">
                    <option value="">Never Expires</option>
                    <option value="1" <?= set_select('expires', '1') ?>>1 Day</option>
                    <option value="7" <?= set_select('expires', '7') ?>>7 Days</option>
                    <option value="30" <?= set_select('expires', '30') ?>>30 Days</option>
                    <option value="90" <?= set_select('expires', '90') ?>>90 Days</option>
                    <option value="365" <?= set_select('expires', '365') ?>>1 Year</option>
                </select>
                <div class="form-text">For security, consider setting an expiration date</div>
            </div>

            <div class="mt-4">
                <h5>Token Permissions</h5>
                <p class="text-muted mb-3">All tokens will have the following permissions:</p>

                <div class="card bg-light mb-3">
                    <div class="card-body">
                        <ul class="mb-0">
                            <li>Make requests to the LLM Proxy API</li>
                            <li>Check quota usage</li>
                            <li>Refresh the token before expiration</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="alert alert-warning mt-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Important:</strong> After creation, the token will only be shown once. Make sure to copy it!
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="<?= site_url('api/tokens') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Token</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>