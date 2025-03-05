<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('buttons/' . $tenant['id']) ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Buttons
        </a>
        <h2>Edit Button for <?= esc($tenant['name']) ?></h2>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-edit me-1"></i>
        Edit Button: <?= esc($button['name']) ?>
    </div>
    <div class="card-body">
        <form action="<?= site_url('buttons/edit/' . $button['id']) ?>" method="post">
            <?= csrf_field() ?>

            <?php if (isset($validation)): ?>
                <div class="alert alert-danger">
                    <?= $validation->listErrors() ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Button Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= set_value('name', $button['name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="domain" class="form-label">Domain</label>
                        <input type="text" class="form-control" id="domain" name="domain" value="<?= set_value('domain', $button['domain']) ?>" required>
                        <div class="form-text">The domain where this button will be used (e.g., example.com)</div>
                    </div>

                    <div class="mb-3">
                        <label for="provider" class="form-label">LLM Provider</label>
                        <select class="form-select" id="provider" name="provider" required>
                            <option value="">Select Provider</option>
                            <?php foreach ($providers as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $key == $button['provider'] ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="model" class="form-label">LLM Model</label>
                        <select class="form-select" id="model" name="model" required>
                            <option value="">Select Model</option>
                            <?php foreach ($models as $provider => $providerModels): ?>
                                <optgroup label="<?= $providers[$provider] ?>" class="model-group" data-provider="<?= $provider ?>">
                                    <?php foreach ($providerModels as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= $key == $button['model'] ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="active" class="form-label">Status</label>
                        <select class="form-select" id="active" name="active">
                            <option value="1" <?= $button['active'] ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= !$button['active'] ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="api_key" class="form-label">API Key</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="api_key" name="api_key" value="<?= $button['api_key'] ? '********' : '' ?>" <?= $button['api_key'] ? 'placeholder="Leave unchanged to keep current key"' : '' ?>>
                            <div class="input-group-text">
                                <input class="form-check-input mt-0 me-2" type="checkbox" id="generate_new_key" name="generate_new_key" value="1">
                                <label class="form-check-label" for="generate_new_key">Generate New</label>
                            </div>
                        </div>
                        <div class="form-text">Leave blank to use the global API key for this provider</div>
                    </div>

                    <div class="mb-3">
                        <label for="system_prompt" class="form-label">System Prompt</label>
                        <textarea class="form-control" id="system_prompt" name="system_prompt" rows="8"><?= set_value('system_prompt', $button['system_prompt']) ?></textarea>
                        <div class="form-text">System instructions for the model that define its behavior</div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="<?= site_url('buttons/' . $tenant['id']) ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Button</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const providerSelect = document.getElementById('provider');
        const modelSelect = document.getElementById('model');
        const modelGroups = document.querySelectorAll('.model-group');

        // Function to filter models based on selected provider
        function filterModels() {
            const selectedProvider = providerSelect.value;

            // Hide all model groups
            modelGroups.forEach(group => {
                group.style.display = 'none';

                // Disable all options in hidden groups
                const options = group.querySelectorAll('option');
                options.forEach(option => {
                    option.disabled = true;
                });
            });

            // Show only the selected provider's models
            if (selectedProvider) {
                const selectedGroup = document.querySelector(`.model-group[data-provider="${selectedProvider}"]`);
                if (selectedGroup) {
                    selectedGroup.style.display = '';

                    // Enable options in the selected group
                    const options = selectedGroup.querySelectorAll('option');
                    options.forEach(option => {
                        option.disabled = false;
                    });
                }
            }
        }

        // Initial filter
        filterModels();

        // Add event listener to provider select
        providerSelect.addEventListener('change', filterModels);

        // API Key generation toggle
        const generateKeyCheckbox = document.getElementById('generate_new_key');
        const apiKeyInput = document.getElementById('api_key');

        generateKeyCheckbox.addEventListener('change', function() {
            apiKeyInput.disabled = this.checked;
            if (this.checked) {
                apiKeyInput.value = '';
            }
        });
    });
</script>

<?= $this->endSection() ?>