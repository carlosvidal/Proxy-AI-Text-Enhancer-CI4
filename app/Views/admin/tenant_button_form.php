<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4">
    <h1 class="mt-4"><?= isset($button) ? 'Edit' : 'Create' ?> Button</h1>
    
    <?php if (session()->has('error')): ?>
        <div class="alert alert-danger"><?= session('error') ?></div>
    <?php endif; ?>

    <?php if (session()->has('success')): ?>
        <div class="alert alert-success"><?= session('success') ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form action="<?= isset($button) ? site_url('admin/buttons/update/' . $button['button_id']) : site_url('admin/buttons/create') ?>" method="post">
                <?= csrf_field() ?>

                <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= old('name', $button['name'] ?? '') ?>" required>
                    <?php if (session('errors.name')): ?>
                        <div class="invalid-feedback"><?= session('errors.name') ?></div>
                    <?php endif; ?>
                    <div class="form-text">A descriptive name for this button</div>
                </div>

                <div class="mb-3">
                    <label for="domain" class="form-label">Domain</label>
                    <input type="text" class="form-control <?= session('errors.domain') ? 'is-invalid' : '' ?>" id="domain" name="domain" value="<?= old('domain', $button['domain'] ?? '') ?>" required>
                    <?php if (session('errors.domain')): ?>
                        <div class="invalid-feedback"><?= session('errors.domain') ?></div>
                    <?php endif; ?>
                    <div class="form-text">Domain where this button will be used (e.g., example.com)</div>
                </div>

                <div class="mb-3">
                    <label for="provider" class="form-label">AI Provider</label>
                    <select class="form-select <?= session('errors.provider') ? 'is-invalid' : '' ?>" id="provider" name="provider" required>
                        <option value="">Select Provider</option>
                        <option value="openai" <?= old('provider', $button['provider'] ?? '') === 'openai' ? 'selected' : '' ?>>OpenAI</option>
                        <option value="anthropic" <?= old('provider', $button['provider'] ?? '') === 'anthropic' ? 'selected' : '' ?>>Anthropic</option>
                        <option value="google" <?= old('provider', $button['provider'] ?? '') === 'google' ? 'selected' : '' ?>>Google</option>
                    </select>
                    <?php if (session('errors.provider')): ?>
                        <div class="invalid-feedback"><?= session('errors.provider') ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="model" class="form-label">Model</label>
                    <input type="text" class="form-control <?= session('errors.model') ? 'is-invalid' : '' ?>" id="model" name="model" value="<?= old('model', $button['model'] ?? '') ?>" required>
                    <?php if (session('errors.model')): ?>
                        <div class="invalid-feedback"><?= session('errors.model') ?></div>
                    <?php endif; ?>
                    <div class="form-text">Model to use (e.g., gpt-4, claude-2)</div>
                </div>

                <div class="mb-3">
                    <label for="api_key" class="form-label">API Key</label>
                    <input type="password" class="form-control <?= session('errors.api_key') ? 'is-invalid' : '' ?>" id="api_key" name="api_key" value="<?= old('api_key', $button['api_key'] ?? '') ?>" <?= isset($button) ? '' : 'required' ?>>
                    <?php if (session('errors.api_key')): ?>
                        <div class="invalid-feedback"><?= session('errors.api_key') ?></div>
                    <?php endif; ?>
                    <div class="form-text"><?= isset($button) ? 'Leave blank to keep current API key' : 'API key for the selected provider' ?></div>
                </div>

                <div class="mb-3">
                    <label for="system_prompt" class="form-label">System Prompt</label>
                    <textarea class="form-control <?= session('errors.system_prompt') ? 'is-invalid' : '' ?>" id="system_prompt" name="system_prompt" rows="4"><?= old('system_prompt', $button['system_prompt'] ?? '') ?></textarea>
                    <?php if (session('errors.system_prompt')): ?>
                        <div class="invalid-feedback"><?= session('errors.system_prompt') ?></div>
                    <?php endif; ?>
                    <div class="form-text">Optional system prompt to guide the AI's behavior</div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="active" name="active" value="1" <?= old('active', $button['active'] ?? '1') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="active">Active</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><?= isset($button) ? 'Update' : 'Create' ?> Button</button>
                <a href="<?= site_url('admin/buttons/' . $tenantId) ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
