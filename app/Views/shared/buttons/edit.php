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

        <form action="<?= 
            isset($isAdmin) && $isAdmin 
                ? site_url('admin/tenants/' . $tenant['tenant_id'] . '/buttons/' . $button['button_id'] . '/update')
                : site_url('buttons/update/' . $button['button_id']) 
        ?>" method="post">
            <?= csrf_field() ?>
            <?php if (isset($isAdmin) && $isAdmin): ?>
                <input type="hidden" name="tenant_id" value="<?= $tenant['tenant_id'] ?>">
                <input type="hidden" name="button_id" value="<?= $button['button_id'] ?>">
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Button ID</label>
                        <input type="text" class="form-control" value="<?= esc($button['button_id']) ?>" disabled>
                        <div class="form-text">Button ID cannot be changed</div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Button Name</label>
                        <input type="text" class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>" 
                               id="name" name="name" value="<?= old('name', $button['name']) ?>" required>
                        <?php if (session('errors.name')): ?>
                            <div class="invalid-feedback"><?= session('errors.name') ?></div>
                        <?php endif; ?>
                        <div class="form-text">A descriptive name for your button</div>
                    </div>

                    <div class="mb-3">
                        <?php
                        if (empty($domains)): ?>
                            <div class="alert alert-warning">
                                No hay dominios configurados. Por favor, configure al menos un dominio.
                            </div>
                            <input type="text" class="form-control <?= session('errors.domain') ? 'is-invalid' : '' ?>"
                                id="domain" name="domain" value="<?= old('domain', $button['domain']) ?>" required>
                        <?php elseif (isset($tenant['max_domains']) && $tenant['max_domains'] > 1 && count($domains) > 1): ?>
                            <label class="form-label">Dominio Permitido</label>
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
                        <div class="form-text">The domain where this button will be used (must start with https://)</div>
                    </div>

                    <div class="mb-3">
                        <label for="api_key_id" class="form-label">API Key</label>
                        <select class="form-select <?= session('errors.api_key_id') ? 'is-invalid' : '' ?>"
                            id="api_key_id" name="api_key_id" required>
                            <option value="">Select API Key</option>
                            <?php
                            // Try to find the current API key in the tenant's API keys
                            $currentApiKeyFound = false;
                            $currentApiKeyId = '';

                            if (isset($apiKeys) && is_array($apiKeys)):
                                foreach ($apiKeys as $apiKey):
                                    if ($apiKey['active'] == 1):
                                        // Check if this might be the current API key (matching provider)
                                        $isCurrentProvider = ($apiKey['provider'] == $button['provider']);

                                        // For old buttons that don't have api_key_id, we'll select based on provider
                                        $selected = old('api_key_id') == $apiKey['api_key_id'] || (!old('api_key_id') && $isCurrentProvider && !$currentApiKeyFound);

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
                            <?php else: ?>
                                <option value="" disabled>No API keys available</option>
                            <?php endif; ?>
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
<!-- Campo Temperatura -->
<div class="mb-3">
    <label for="temperature" class="form-label">Temperatura
        <span id="temperature-value" class="ms-2 fw-bold"><?= old('temperature', $button['temperature'] ?? '0.7') ?></span>
    </label>
    <input type="range" class="form-range" min="0" max="1" step="0.01" id="temperature" name="temperature" value="<?= old('temperature', $button['temperature'] ?? '0.7') ?>" oninput="document.getElementById('temperature-value').textContent = this.value">
    <div class="form-text">Controla la creatividad del modelo (0 = determinista, 1 = más creativo).</div>
</div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="system_prompt" class="form-label">System Prompt</label>
                        <textarea class="form-control <?= session('errors.system_prompt') ? 'is-invalid' : '' ?>" 
                                  id="system_prompt" name="system_prompt" rows="8"><?= old('system_prompt', $button['system_prompt'] ?? '') ?></textarea>
                        <?php if (session('errors.system_prompt')): ?>
                            <div class="invalid-feedback"><?= session('errors.system_prompt') ?></div>
                        <?php endif; ?>
                        <div class="form-text">System instructions for the model that define its behavior</div>
                    </div>

                    <div class="mb-3">
    <label for="status" class="form-label">Status</label><br>
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="status" name="status" value="active" <?= old('status', $button['status']) == 'active' ? 'checked' : '' ?>>
        <label class="form-check-label" for="status">
            <span id="status-label">Active</span>
        </label>
    </div>
    <?php if (session('errors.status')): ?>
        <div class="invalid-feedback d-block"><?= session('errors.status') ?></div>
    <?php endif; ?>
</div>
<!-- Checkbox para auto_create_api_users -->
<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="auto_create_api_users" id="auto_create_api_users" value="1"
            <?= old('auto_create_api_users', $button['auto_create_api_users'] ?? 0) ? 'checked' : '' ?>>
        <label class="form-check-label" for="auto_create_api_users">
            Crear usuarios API automáticamente si no existen
        </label>
    </div>
    <div class="form-text">Si está activado, los usuarios API se crearán automáticamente al usarse por primera vez con este botón.</div>
</div>
<script>
    // Cambia el texto del label según el estado del toggle
    document.addEventListener('DOMContentLoaded', function() {
        var statusCheckbox = document.getElementById('status');
        var statusLabel = document.getElementById('status-label');
        function updateLabel() {
            statusLabel.textContent = statusCheckbox.checked ? 'Active' : 'Inactive';
        }
        statusCheckbox.addEventListener('change', updateLabel);
        updateLabel();
    });
</script>
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
    const apiKeySelect = document.getElementById('api_key_id');
    const modelSelect = document.getElementById('model');
    const modelGroups = document.querySelectorAll('.model-group');

    // Function to filter models based on selected API key's provider
    function filterModels() {
        const selectedOption = apiKeySelect.options[apiKeySelect.selectedIndex];
        const selectedProvider = selectedOption ? selectedOption.getAttribute('data-provider') : null;

        // Hide all model groups
        modelGroups.forEach(group => {
            group.style.display = 'none';
            group.querySelectorAll('option').forEach(option => option.disabled = true);
        });

        // Show only the selected provider's models
        if (selectedProvider) {
            const selectedGroup = document.querySelector(`.model-group[data-provider="${selectedProvider}"]`);
            if (selectedGroup) {
                selectedGroup.style.display = '';
                selectedGroup.querySelectorAll('option').forEach(option => option.disabled = false);

                // Select first option if none selected
                if (!modelSelect.value || !selectedGroup.querySelector(`option[value="${modelSelect.value}"]`)) {
                    const firstOption = selectedGroup.querySelector('option');
                    if (firstOption) {
                        modelSelect.value = firstOption.value;
                    }
                }
            }
        }
    }

    // Filter models on API key change
    apiKeySelect.addEventListener('change', filterModels);

    // Initial filter
    filterModels();
});
</script>

<?= $this->endSection() ?>