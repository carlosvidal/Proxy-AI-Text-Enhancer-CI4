<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('buttons') ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Buttons
        </a>
        <h2>Edit Button for <?= esc($tenant['name']) ?></h2>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-edit me-1"></i>
        Edit Button
    </div>
    <div class="card-body">
        <?php if (session()->has('error')): ?>
            <div class="alert alert-danger">
                <?= session('error') ?>
            </div>
        <?php endif; ?>

        <form action="<?= site_url('buttons/edit/' . $button['button_id']) ?>" method="post">
            <?= csrf_field() ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Button Name</label>
                        <input type="text" class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>" 
                               id="name" name="name" value="<?= old('name', $button['name']) ?>" required>
                        <?php if (session('errors.name')): ?>
                            <div class="invalid-feedback"><?= session('errors.name') ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="domain" class="form-label">Domain</label>
                        <input type="text" class="form-control <?= session('errors.domain') ? 'is-invalid' : '' ?>" 
                               id="domain" name="domain" value="<?= old('domain', $button['domain']) ?>" required>
                        <?php if (session('errors.domain')): ?>
                            <div class="invalid-feedback"><?= session('errors.domain') ?></div>
                        <?php endif; ?>
                        <div class="form-text">The domain where this button will be used (e.g., example.com)</div>
                    </div>

                    <div class="mb-3">
                        <label for="provider" class="form-label">LLM Provider</label>
                        <select class="form-select <?= session('errors.provider') ? 'is-invalid' : '' ?>" 
                                id="provider" name="provider" required>
                            <option value="">Select Provider</option>
                            <?php foreach ($providers as $key => $label): ?>
                                <option value="<?= $key ?>" <?= old('provider', $button['provider']) == $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (session('errors.provider')): ?>
                            <div class="invalid-feedback"><?= session('errors.provider') ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="model" class="form-label">LLM Model</label>
                        <select class="form-select <?= session('errors.model') ? 'is-invalid' : '' ?>" 
                                id="model" name="model" required>
                            <option value="">Select Model</option>
                            <?php foreach ($models as $provider => $providerModels): ?>
                                <optgroup label="<?= $providers[$provider] ?>" class="model-group" data-provider="<?= $provider ?>">
                                    <?php foreach ($providerModels as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= old('model', $button['model']) == $key ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                        <?php if (session('errors.model')): ?>
                            <div class="invalid-feedback"><?= session('errors.model') ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="api_key" class="form-label">Provider API Key</label>
                        <input type="password" class="form-control <?= session('errors.api_key') ? 'is-invalid' : '' ?>" 
                               id="api_key" name="api_key">
                        <?php if (session('errors.api_key')): ?>
                            <div class="invalid-feedback"><?= session('errors.api_key') ?></div>
                        <?php endif; ?>
                        <div class="form-text">
                            Enter a new API key to update the current one.
                            <strong>Note:</strong> Leave blank to keep the current API key.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="system_prompt" class="form-label">System Prompt</label>
                        <textarea class="form-control <?= session('errors.system_prompt') ? 'is-invalid' : '' ?>" 
                                  id="system_prompt" name="system_prompt" rows="8"><?= old('system_prompt', $button['system_prompt']) ?></textarea>
                        <?php if (session('errors.system_prompt')): ?>
                            <div class="invalid-feedback"><?= session('errors.system_prompt') ?></div>
                        <?php endif; ?>
                        <div class="form-text">System instructions for the model that define its behavior</div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="<?= site_url('buttons') ?>" class="btn btn-secondary">Cancel</a>
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