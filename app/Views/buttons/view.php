<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('buttons/' . $tenant['id']) ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Buttons
        </a>
        <h2><?= esc($button['name']) ?> <span class="badge badge-tenant"><?= esc($button['domain']) ?></span></h2>
    </div>
    <div>
        <a href="<?= site_url('buttons/edit/' . $button['id']) ?>" class="btn btn-warning text-white">
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
                    <tr>
                        <th>Domain:</th>
                        <td><?= esc($button['domain']) ?></td>
                    </tr>
                    <tr>
                        <th>Provider:</th>
                        <td><span class="badge badge-provider"><?= esc($button['provider']) ?></span></td>
                    </tr>
                    <tr>
                        <th>Model:</th>
                        <td><span class="badge badge-model"><?= esc($button['model']) ?></span></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <?php if ($button['active']): ?>
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
                    <tr>
                        <th>API Key:</th>
                        <td>
                            <?php if ($button['api_key']): ?>
                                <div class="api-key-status">
                                    <span class="badge bg-success">Custom API Key Set</span>
                                    <span class="text-muted ms-2">••••••<?= substr($button['api_key'], -4) ?></span>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-secondary">Using Global API Key</span>
                            <?php endif; ?>
                        </td>
                    </tr>
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
                <?php if (empty($button['system_prompt'])): ?>
                    <p class="text-muted text-center">No system prompt defined for this button.</p>
                <?php else: ?>
                    <pre class="system-prompt"><?= esc($button['system_prompt']) ?></pre>
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
        <p>To use this button in your application, you'll need to make API requests using the following parameters:</p>

        <div class="mb-4">
            <h5>Endpoint</h5>
            <pre class="bg-light p-3 rounded"><code>POST <?= base_url('api/llm-proxy') ?></code></pre>
        </div>


        <div class="mb-4">
            <h5>Request Body</h5>
            <p>You can easily integrate this button using your own Web Component:</p>
            <pre class="bg-light p-3 rounded">
<code>{
    "tenantId": "<?= esc($tenant['tenant_id']) ?>",
    "buttonId": "<?= esc($button['button_id']) ?>",
    "userId": "[USER_ID]",
    "messages": [
        { "role": "user", "content": "Your message here" }
    ],
    "stream": true  // Optional, set to false for non-streaming responses
}</code>
        </pre>
        </div>
        <div class="row">
            <div class="col-12 col-md-8 mb-4">
                <h5>Web Component Integration</h5>
                <p>You can easily integrate this button in your website using our Web Component:</p>
                <pre class="bg-light p-3 rounded"><code>&lt;ai-text-enhancer  
    id="<?= esc($button['button_id']) ?>" 
    editor-id="[TARGET-INPUT]" 
    language="es"
    tenant-id="<?= esc($tenant['tenant_id']) ?>"
    user-id="[USER_ID]"
    proxy-endpoint="<?= base_url('api/llm-proxy') ?>"&gt;
&lt;/ai-text-enhancer&gt;
&lt;script type="module"&gt;
import "https://cdn.jsdelivr.net/gh/carlosvidal/AI-Text-Enhancer/dist/ai-text-enhancer.umd.js?purge" 
&lt;/script&gt;</code></pre>
            </div>
            <div class="col-12 col-md-4">
                <strong>Preview</strong>
                <textarea id="target-wysiswyg" class="form-control" rows="5" placeholder="Type your message here..."></textarea>
                <ai-text-enhancer
                    id="59200d9fa9791103"
                    editor-id="target-wysiswyg"
                    language="es"
                    tenant-id="miti8f58"
                    user-id="[USER_ID]"
                    proxy-endpoint="http://llmproxy2.test:8080/api/llm-proxy">
                </ai-text-enhancer>
                <script type="module">
                    import "https://cdn.jsdelivr.net/gh/carlosvidal/AI-Text-Enhancer/dist/ai-text-enhancer.umd.js?purge"
                </script>
            </div>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Important:</strong> Replace <code>[USER_ID]</code> with your actual user identifier. If the user doesn't exist yet, they will be automatically created.
            </div>
        </div>
    </div>

    <style>
        .system-prompt {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.25rem;
            border: 1px solid #dee2e6;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 0.9rem;
            color: #495057;
        }
    </style>

    <script>
        function copyToClipboard(text) {
            // Create a temporary textarea
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);

            // Select and copy
            textarea.select();
            document.execCommand('copy');

            // Remove the textarea
            document.body.removeChild(textarea);

            // Show a notification
            const notification = document.createElement('div');
            notification.className = 'position-fixed top-50 start-50 translate-middle p-3 bg-dark text-white rounded';
            notification.style.zIndex = 9999;
            notification.innerHTML = '<i class="fas fa-check-circle me-2"></i>Copied to clipboard!';

            document.body.appendChild(notification);

            // Remove notification after 2 seconds
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 2000);
        }
    </script>

    <?= $this->endSection() ?>