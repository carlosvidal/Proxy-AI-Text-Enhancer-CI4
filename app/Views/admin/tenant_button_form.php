<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/buttons') ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Buttons
        </a>
        <h2><?= isset($button) ? 'Edit' : 'Create' ?> Button - <?= esc($tenant['name']) ?></h2>
        <p class="text-muted mb-0">Configure text enhancement button settings</p>
    </div>
</div>

<?php if (session()->has('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->has('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            <?php foreach (session('errors') as $error): ?>
                <li><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-code me-1"></i>
        Button Configuration
    </div>
    <div class="card-body">
        <form action="<?= current_url() ?>" method="post">
            <?= csrf_field() ?>

            <!-- Basic Information -->
            <div class="mb-4">
                <h5>Basic Information</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Button Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?= old('name', $button['name'] ?? '') ?>" required
                               placeholder="e.g., Make Professional">
                        <div class="form-text">A descriptive name for the button (3-255 characters)</div>
                    </div>

                    <div class="col-md-6">
                        <label for="type" class="form-label">Button Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="standard" <?= old('type', $button['type'] ?? '') === 'standard' ? 'selected' : '' ?>>
                                Standard
                            </option>
                            <option value="custom" <?= old('type', $button['type'] ?? '') === 'custom' ? 'selected' : '' ?>>
                                Custom
                            </option>
                        </select>
                        <div class="form-text">Choose the button type</div>
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"
                                placeholder="Optional description of what this button does"><?= old('description', $button['description'] ?? '') ?></textarea>
                        <div class="form-text">A brief description of the button's purpose (optional, max 1000 characters)</div>
                    </div>
                </div>
            </div>

            <!-- Prompt Configuration -->
            <div class="mb-4">
                <h5>Prompt Configuration</h5>
                <div class="row g-3">
                    <div class="col-12">
                        <label for="prompt" class="form-label">Main Prompt <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="prompt" name="prompt" rows="4" required
                                placeholder="Enter the main prompt template that will be used to enhance the text"><?= old('prompt', $button['prompt'] ?? '') ?></textarea>
                        <div class="form-text">
                            The main prompt template. Use {text} as a placeholder for the user's input.
                            Example: "Make the following text more professional while maintaining its meaning: {text}"
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="system_prompt" class="form-label">System Prompt</label>
                        <textarea class="form-control" id="system_prompt" name="system_prompt" rows="3"
                                placeholder="Optional system prompt to guide the AI's behavior"><?= old('system_prompt', $button['system_prompt'] ?? '') ?></textarea>
                        <div class="form-text">
                            Optional system prompt to set the context and behavior of the AI.
                            Example: "You are a professional editor helping to improve text clarity and professionalism."
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Settings -->
            <div class="mb-4">
                <h5>Advanced Settings</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="provider" class="form-label">Provider</label>
                        <select class="form-select" id="provider" name="provider">
                            <option value="openai" <?= old('provider', $button['provider'] ?? 'openai') === 'openai' ? 'selected' : '' ?>>
                                OpenAI
                            </option>
                            <option value="anthropic" <?= old('provider', $button['provider'] ?? '') === 'anthropic' ? 'selected' : '' ?>>
                                Anthropic
                            </option>
                            <option value="cohere" <?= old('provider', $button['provider'] ?? '') === 'cohere' ? 'selected' : '' ?>>
                                Cohere
                            </option>
                        </select>
                        <div class="form-text">Select the AI provider for this button</div>
                    </div>

                    <div class="col-md-6">
                        <label for="model" class="form-label">Model</label>
                        <select class="form-select" id="model" name="model">
                            <!-- OpenAI Models -->
                            <optgroup label="OpenAI" class="provider-models openai-models">
                                <option value="gpt-3.5-turbo" <?= old('model', $button['model'] ?? 'gpt-3.5-turbo') === 'gpt-3.5-turbo' ? 'selected' : '' ?>>
                                    GPT-3.5 Turbo
                                </option>
                                <option value="gpt-4" <?= old('model', $button['model'] ?? '') === 'gpt-4' ? 'selected' : '' ?>>
                                    GPT-4
                                </option>
                            </optgroup>
                            <!-- Anthropic Models -->
                            <optgroup label="Anthropic" class="provider-models anthropic-models" style="display:none;">
                                <option value="claude-2" <?= old('model', $button['model'] ?? '') === 'claude-2' ? 'selected' : '' ?>>
                                    Claude 2
                                </option>
                                <option value="claude-instant" <?= old('model', $button['model'] ?? '') === 'claude-instant' ? 'selected' : '' ?>>
                                    Claude Instant
                                </option>
                            </optgroup>
                            <!-- Cohere Models -->
                            <optgroup label="Cohere" class="provider-models cohere-models" style="display:none;">
                                <option value="command" <?= old('model', $button['model'] ?? '') === 'command' ? 'selected' : '' ?>>
                                    Command
                                </option>
                                <option value="command-light" <?= old('model', $button['model'] ?? '') === 'command-light' ? 'selected' : '' ?>>
                                    Command Light
                                </option>
                            </optgroup>
                        </select>
                        <div class="form-text">Select the model to use for this button</div>
                    </div>

                    <div class="col-md-6">
                        <label for="temperature" class="form-label">Temperature</label>
                        <input type="number" class="form-control" id="temperature" name="temperature" 
                               value="<?= old('temperature', $button['temperature'] ?? '0.7') ?>"
                               min="0" max="2" step="0.1">
                        <div class="form-text">Controls randomness in the output (0.0 to 2.0, default: 0.7)</div>
                    </div>

                    <div class="col-md-6">
                        <label for="max_tokens" class="form-label">Max Tokens</label>
                        <input type="number" class="form-control" id="max_tokens" name="max_tokens" 
                               value="<?= old('max_tokens', $button['max_tokens'] ?? '2048') ?>"
                               min="1" max="4096">
                        <div class="form-text">Maximum number of tokens in the response (1 to 4096, default: 2048)</div>
                    </div>

                    <div class="col-12">
                        <label for="api_key" class="form-label">Custom API Key</label>
                        <input type="password" class="form-control" id="api_key" name="api_key" 
                               value="<?= old('api_key', $button['api_key'] ?? '') ?>"
                               placeholder="Leave blank to use tenant's default API key">
                        <div class="form-text">Optional: Provide a custom API key for this button. If left blank, the tenant's default API key will be used.</div>
                    </div>

                    <?php if (isset($button)): ?>
                    <div class="col-md-6">
                        <label for="active" class="form-label">Status</label>
                        <select class="form-select" id="active" name="active">
                            <option value="1" <?= old('active', $button['active'] ?? '1') == '1' ? 'selected' : '' ?>>
                                Active
                            </option>
                            <option value="0" <?= old('active', $button['active'] ?? '1') == '0' ? 'selected' : '' ?>>
                                Inactive
                            </option>
                        </select>
                        <div class="form-text">Control whether this button is available for use</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/buttons') ?>" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <?= isset($button) ? 'Update' : 'Create' ?> Button
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Provider-Model Selection Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const providerSelect = document.getElementById('provider');
    const modelSelect = document.getElementById('model');
    const modelGroups = document.querySelectorAll('.provider-models');
    
    function updateModelOptions() {
        const selectedProvider = providerSelect.value;
        
        // Hide all model groups
        modelGroups.forEach(group => {
            group.style.display = 'none';
        });
        
        // Show only the models for the selected provider
        const selectedModels = document.querySelector('.' + selectedProvider + '-models');
        if (selectedModels) {
            selectedModels.style.display = '';
            
            // Select the first option in the group if current selection is not from this provider
            const currentOption = modelSelect.selectedOptions[0];
            if (!currentOption.closest('optgroup') || !currentOption.closest('optgroup').classList.contains(selectedProvider + '-models')) {
                const firstOption = selectedModels.querySelector('option');
                if (firstOption) {
                    firstOption.selected = true;
                }
            }
        }
    }
    
    providerSelect.addEventListener('change', updateModelOptions);
    updateModelOptions(); // Run on page load
});
</script>

<?= $this->endSection() ?>
