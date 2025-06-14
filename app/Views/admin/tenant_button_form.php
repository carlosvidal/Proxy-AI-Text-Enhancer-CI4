<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/buttons') ?>" class="btn btn-secondary btn-sm mb-2">
                <i class="fas fa-arrow-left me-1"></i>Back to Buttons
            </a>
            <h2><?= isset($button) ? 'Edit' : 'Create' ?> Button - <?= esc($tenant['name']) ?></h2>
        </div>
    </div>
    
    <?php if (session('error')): ?>
        <div class="alert alert-danger"><?= session('error') ?></div>
    <?php endif; ?>

    <?php if (session('success')): ?>
        <div class="alert alert-success"><?= session('success') ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-code me-1"></i>
            <?= isset($button) ? 'Edit' : 'Create' ?> Button
        </div>
        <div class="card-body">
            <?php
// Reutilizar el formulario avanzado de shared/buttons/create.php
// Se debe asegurar que las variables $tenant, $apiKeys, $providers, $models estén definidas
// Si no están, cargarlas aquí (opcional, según el controlador)
include(APPPATH . 'Views/shared/buttons/create.php');
?>
                                <?php foreach ($providers as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= old('provider', $button['provider'] ?? '') == $key ? 'selected' : '' ?>><?= $label ?></option>
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
                                            <option value="<?= $key ?>" <?= old('model', $button['model'] ?? '') == $key ? 'selected' : '' ?>><?= $label ?></option>
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
                                   id="api_key" name="api_key" <?= isset($button) ? '' : 'required' ?>>
                            <?php if (session('errors.api_key')): ?>
                                <div class="invalid-feedback"><?= session('errors.api_key') ?></div>
                            <?php endif; ?>
                            <div class="form-text">
                                <?= isset($button) ? 'Leave blank to keep current API key' : 'API key for the selected provider' ?>.
                                This key will be securely encrypted before storage.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="system_prompt" class="form-label">System Prompt</label>
                            <textarea class="form-control <?= session('errors.system_prompt') ? 'is-invalid' : '' ?>" 
                                      id="system_prompt" name="system_prompt" rows="8"><?= old('system_prompt', $button['system_prompt'] ?? '') ?></textarea>
                            <?php if (session('errors.system_prompt')): ?>
                                <div class="invalid-feedback"><?= session('errors.system_prompt') ?></div>
                            <?php endif; ?>
                            <div class="form-text">System instructions for the model that define its behavior</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/buttons') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><?= isset($button) ? 'Update' : 'Create' ?> Button</button>
                </div>
            </form>
        </div>
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
