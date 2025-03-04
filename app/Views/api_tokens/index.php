<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>API Tokens</h2>
    <a href="<?= site_url('api/tokens/create') ?>" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Create Token
    </a>
</div>

<?php if (session()->getFlashdata('token')): ?>
    <div class="alert alert-success">
        <h5><i class="fas fa-check-circle me-2"></i>Token Created Successfully</h5>
        <p class="mb-2">Your API token has been created. This is the only time the token will be shown, so copy it now:</p>

        <div class="mb-3">
            <label class="form-label"><strong>Access Token</strong> (Include this in your Authorization header)</label>
            <div class="input-group mb-2">
                <input type="text" class="form-control font-monospace" value="<?= session()->getFlashdata('token') ?>" readonly id="tokenField">
                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('tokenField')">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <div class="form-text">Format for use: <code>Authorization: Bearer [your-token]</code></div>
        </div>

        <div class="mb-3">
            <label class="form-label"><strong>Refresh Token</strong> (Use this to get a new access token when it expires)</label>
            <div class="input-group mb-2">
                <input type="text" class="form-control font-monospace" value="<?= session()->getFlashdata('refresh_token') ?>" readonly id="refreshTokenField">
                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('refreshTokenField')">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-key me-1"></i>
        Your API Tokens
    </div>
    <div class="card-body">
        <?php if (empty($tokens)): ?>
            <p class="text-muted text-center">You don't have any API tokens yet.</p>
            <div class="text-center">
                <a href="<?= site_url('api/tokens/create') ?>" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create Your First Token
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Tenant</th>
                            <th>Created</th>
                            <th>Last Used</th>
                            <th>Expires</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tokens as $token): ?>
                            <tr>
                                <td><?= esc($token['name']) ?></td>
                                <td>
                                    <?php if (empty($token['tenant_id'])): ?>
                                        <span class="badge bg-secondary">All Tenants</span>
                                    <?php else: ?>
                                        <span class="badge badge-tenant"><?= esc($token['tenant_id']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('Y-m-d H:i', strtotime($token['created_at'])) ?></td>
                                <td>
                                    <?php if (!empty($token['last_used_at'])): ?>
                                        <?= date('Y-m-d H:i', strtotime($token['last_used_at'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($token['expires_at'])): ?>
                                        <?= date('Y-m-d', strtotime($token['expires_at'])) ?>
                                    <?php else: ?>
                                        <span class="badge bg-success">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= site_url('api/tokens/revoke/' . $token['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to revoke this token? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Revoke
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <i class="fas fa-info-circle me-1"></i>
        Using API Tokens
    </div>
    <div class="card-body">
        <h5>Authentication</h5>
        <p>Include your token in the Authorization header of your API requests:</p>
        <pre><code>Authorization: Bearer your-token-here</code></pre>

        <h5 class="mt-4">API Endpoints</h5>
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Endpoint</th>
                    <th>Method</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>/api/llm-proxy/secure</code></td>
                    <td>POST</td>
                    <td>Make requests to LLM providers (requires authentication)</td>
                </tr>
                <tr>
                    <td><code>/api/quota/secure</code></td>
                    <td>GET</td>
                    <td>Check your token usage quota (requires authentication)</td>
                </tr>
                <tr>
                    <td><code>/api/auth/refresh</code></td>
                    <td>POST</td>
                    <td>Get a new access token using your refresh token</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    function copyToClipboard(elementId) {
        const copyText = document.getElementById(elementId);
        copyText.select();
        copyText.setSelectionRange(0, 99999); // For mobile devices
        document.execCommand("copy");

        // Alert the copied text
        const tooltip = document.createElement("div");
        tooltip.className = "copy-tooltip";
        tooltip.innerHTML = "Copied!";
        document.body.appendChild(tooltip);

        // Remove after animation
        setTimeout(() => {
            document.body.removeChild(tooltip);
        }, 2000);
    }
</script>

<style>
    .copy-tooltip {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        z-index: 9999;
        animation: fadeInOut 2s ease;
    }

    @keyframes fadeInOut {
        0% {
            opacity: 0;
        }

        20% {
            opacity: 1;
        }

        80% {
            opacity: 1;
        }

        100% {
            opacity: 0;
        }
    }
</style>

<?= $this->endSection() ?>