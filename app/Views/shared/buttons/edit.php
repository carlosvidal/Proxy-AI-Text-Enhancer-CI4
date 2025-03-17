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

        <form action="<?= site_url('buttons/update/' . $button['button_id']) ?>" method="post">
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
                        <label for="domain" class="form-label">Dominio Permitido</label>
                        <?php 
                        if(empty($domains)): ?>
                            <div class="alert alert-warning">
                                No hay dominios configurados. Por favor, configure al menos un dominio.
                            </div>
                            <input type="text" class="form-control <?= session('errors.domain') ? 'is-invalid' : '' ?>" 
                                   id="domain" name="domain" value="<?= old('domain', $button['domain']) ?>" required>
                        <?php elseif(isset($tenant['max_domains']) && $tenant['max_domains'] > 1 && count($domains) > 1): ?>
                            <select class="form-select <?= session('errors.domain') ? 'is-invalid' : '' ?>" 
                                   id="domain" name="domain" required>
                                <option value="">Selecciona un dominio</option>
                                <?php foreach ($domains as $d): ?>
                                    <option value="<?= $d['domain'] ?>" <?= old('domain', $button['domain']) == $d['domain'] ? 'selected' : '' ?>>
                                        <?= $d['domain'] ?>
                                        <?= isset($d['verified']) && $d['verified'] ? '' : ' (Pendiente de Verificación)' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="hidden" name="domain" value="<?= $domains[0]['domain'] ?>">
                            <input type="text" class="form-control" value="<?= $domains[0]['domain'] ?> <?= isset($domains[0]['verified']) && $domains[0]['verified'] ? '' : ' (Pendiente de Verificación)' ?>" disabled>
                        <?php endif; ?>
                        
                        <?php if (session('errors.domain')): ?>
                            <div class="invalid-feedback"><?= session('errors.domain') ?></div>
                        <?php endif; ?>
                        <div class="form-text">El dominio donde se usará este botón</div>
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
                        <label for="api_key_id" class="form-label">API Key</label>
                        <select class="form-select <?= session('errors.api_key_id') ? 'is-invalid' : '' ?>" 
                               id="api_key_id" name="api_key_id" required>
                            <option value="">Select API Key</option>
                            <?php 
                            // Try to find the current API key in the tenant's API keys
                            $currentApiKeyFound = false;
                            $currentApiKeyId = '';
                            
                            foreach ($apiKeys as $apiKey): 
                                if ($apiKey['active'] == 1):
                                    // Check if this might be the current API key (matching provider)
                                    $isCurrentProvider = ($apiKey['provider'] == $button['provider']);
                                    
                                    // For old buttons that don't have api_key_id, we'll select based on provider
                                    $selected = old('api_key_id') == $apiKey['api_key_id'] || 
                                              (!old('api_key_id') && $isCurrentProvider && !$currentApiKeyFound);
                                    
                                    if ($selected && $isCurrentProvider) {
                                        $currentApiKeyFound = true;
                                        $currentApiKeyId = $apiKey['api_key_id'];
                                    }
                            ?>
                                    <option value="<?= $apiKey['api_key_id'] ?>" 
                                            data-provider="<?= $apiKey['provider'] ?>"
                                            <?= $selected ? 'selected' : '' ?>>
                                        <?= $apiKey['name'] ?> (<?= $providers[$apiKey['provider']] ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <?php if (session('errors.api_key_id')): ?>
                            <div class="invalid-feedback"><?= session('errors.api_key_id') ?></div>
                        <?php endif; ?>
                        <div class="form-text">
                            Select an API key from your configured keys.
                            <a href="<?= site_url('api-keys/create') ?>">Add a new API key</a>
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
        const apiKeySelect = document.getElementById('api_key_id');
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

        // Function to filter API keys based on selected provider
        function filterApiKeys() {
            const selectedProvider = providerSelect.value;
            const apiKeyOptions = apiKeySelect.querySelectorAll('option');
            
            // First option is always "Select API Key"
            let firstOption = apiKeyOptions[0];
            
            // Hide all API key options except the first one
            apiKeyOptions.forEach(option => {
                if (option !== firstOption) {
                    const optionProvider = option.getAttribute('data-provider');
                    if (selectedProvider && optionProvider !== selectedProvider) {
                        option.style.display = 'none';
                        option.disabled = true;
                    } else {
                        option.style.display = '';
                        option.disabled = false;
                    }
                }
            });
            
            // Reset selection if current selection is now hidden
            const selectedOption = apiKeySelect.options[apiKeySelect.selectedIndex];
            if (selectedOption && selectedOption !== firstOption && selectedOption.disabled) {
                apiKeySelect.value = '';
            }
            
            // If there's only one valid option (besides the placeholder), select it
            let validOptions = Array.from(apiKeyOptions).filter(option => 
                option !== firstOption && !option.disabled
            );
            
            if (validOptions.length === 1) {
                validOptions[0].selected = true;
            }
        }

        // Initial filter
        filterModels();
        filterApiKeys();

        // Add event listener to provider select
        providerSelect.addEventListener('change', function() {
            filterModels();
            filterApiKeys();
        });
        
        // Add event listener to API key select to update provider
        apiKeySelect.addEventListener('change', function() {
            const selectedOption = apiKeySelect.options[apiKeySelect.selectedIndex];
            if (selectedOption && selectedOption.getAttribute('data-provider')) {
                const provider = selectedOption.getAttribute('data-provider');
                providerSelect.value = provider;
                filterModels();
            }
        });
    });
</script>

<?= $this->endSection() ?>