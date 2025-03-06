<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('buttons/' . $tenant['id']) ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Buttons
        </a>
        <h2>Create New Button for <?= esc($tenant['name']) ?></h2>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-plus-circle me-1"></i>
        Create New Button
    </div>
    <div class="card-body">
        <form action="<?= site_url('buttons/create/' . $tenant['id']) ?>" method="post">
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
                        <input type="text" class="form-control" id="name" name="name" value="<?= set_value('name') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="domain" class="form-label">Domain</label>
                        <input type="text" class="form-control" id="domain" name="domain" value="<?= set_value('domain') ?>" required>
                        <div class="form-text">The domain where this button will be used (e.g., example.com)</div>
                    </div>

                    <div class="mb-3">
                        <label for="provider" class="form-label">LLM Provider</label>
                        <select class="form-select" id="provider" name="provider" required>
                            <option value="">Select Provider</option>
                            <?php foreach ($providers as $key => $label): ?>
                                <option value="<?= $key ?>" <?= set_select('provider', $key) ?>><?= $label ?></option>
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
                                        <option value="<?= $key ?>" <?= set_select('model', $key) ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="api_key" class="form-label">Provider API Key</label>
                        <input type="password" class="form-control" id="api_key" name="api_key" value="<?= set_value('api_key') ?>">
                        <div class="form-text">
                            Enter the API key obtained from the LLM provider. Leave blank to use the global API key.
                            <strong>Note:</strong> This key will be securely stored and never displayed in full again.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="system_prompt" class="form-label">System Prompt</label>
                        <textarea class="form-control" id="system_prompt" name="system_prompt" rows="8"><?= set_value('system_prompt') ?></textarea>
                        <div class="form-text">System instructions for the model that define its behavior</div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="<?= site_url('buttons/' . $tenant['id']) ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Button</button>
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

                    // Select the first option of the group if none selected
                    if (!modelSelect.value || !selectedGroup.querySelector(`option[value="${modelSelect.value}"]`)) {
                        const firstOption = selectedGroup.querySelector('option');
                        if (firstOption) {
                            firstOption.selected = true;
                        }
                    }
                }
            }
        }

        // Initial filter
        filterModels();

        // Add event listener to provider select
        providerSelect.addEventListener('change', filterModels);
    });
</script>

<?= $this->endSection() ?>