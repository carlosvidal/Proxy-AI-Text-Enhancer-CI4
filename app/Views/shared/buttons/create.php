<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('buttons') ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Buttons
        </a>
        <h2>Create New Button</h2>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-plus-circle me-1"></i>
        Create New Button
    </div>
    <div class="card-body">
        <?php if (session('error')): ?>
            <div class="alert alert-danger">
                <?= session('error') ?>
            </div>
        <?php endif; ?>

        <form action="<?= site_url('buttons/store') ?>" method="post">
            <?= csrf_field() ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Button Name</label>
                        <input type="text" class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>" 
                               id="name" name="name" value="<?= old('name') ?>" required>
                        <?php if (session('errors.name')): ?>
                            <div class="invalid-feedback"><?= session('errors.name') ?></div>
                        <?php endif; ?>
                        <div class="form-text">A descriptive name for your button</div>
                    </div>

                    <div class="mb-3">
                        <?php
// Usar $domains pasado desde el controlador si existe, si no, obtenerlo del modelo por compatibilidad legacy
if (!isset($domains)) {
    if (isset($tenant['tenant_id'])) {
        $tenantsModel = new \App\Models\TenantsModel();
        $domains = $tenantsModel->getDomains($tenant['tenant_id']);
    } else {
        $domains = [];
    }
}
?>
<?php if (empty($domains)) { ?>
    <div class="alert alert-warning">
        No hay dominios configurados para este tenant. Pídale al superadmin que registre al menos uno desde la administración.
    </div>
    <select name="domain" class="form-select" required disabled>
        <option value="">Sin dominios disponibles</option>
    </select>
<?php } else { ?>
    <label class="form-label">Dominio Permitido</label>
    <select name="domain" id="domain" class="form-select" required>
        <option value="__tenant__" <?= old('domain') == '__tenant__' ? 'selected' : '' ?>>
            Todos los dominios del tenant
        </option>
        <?php foreach ($domains as $domain) { ?>
            <option value="<?= $domain['domain'] ?>" <?= old('domain') == $domain['domain'] ? 'selected' : '' ?>>
                <?= $domain['domain'] ?>
                <?= isset($domain['verified']) && $domain['verified'] ? '' : ' (Pendiente de Verificación)' ?>
            </option>
        <?php } ?>
    </select>
    <div class="form-text">Selecciona "Todos los dominios del tenant" para que este botón funcione en cualquier dominio registrado.</div>
<?php } ?>
                    </div>

                    <div class="mb-3">
                        <label for="api_key_id" class="form-label">API Key</label>
                        <select class="form-select <?= session('errors.api_key_id') ? 'is-invalid' : '' ?>" 
                               id="api_key_id" name="api_key_id" required>
                            <option value="">Select API Key</option>
                            <?php foreach ($apiKeys as $apiKey): ?>
                                <?php if ($apiKey['active'] == 1): ?>
                                    <option value="<?= $apiKey['api_key_id'] ?>" 
                                            data-provider="<?= $apiKey['provider'] ?>"
                                            <?= old('api_key_id') == $apiKey['api_key_id'] ? 'selected' : '' ?>>
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
                        <label for="model" class="form-label">LLM Model</label>
                        <select class="form-select <?= session('errors.model') ? 'is-invalid' : '' ?>" 
                                id="model" name="model" required>
                            <option value="">Select Model</option>
                            <?php foreach ($models as $provider => $providerModels): ?>
                                <optgroup label="<?= $providers[$provider] ?>" class="model-group" data-provider="<?= $provider ?>">
                                    <?php foreach ($providerModels as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= old('model') == $key ? 'selected' : '' ?>><?= $label ?></option>
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
                            <span id="temperature-value" class="ms-2 fw-bold"><?= old('temperature', '0.7') ?></span>
                        </label>
                        <input type="range" class="form-range" min="0" max="1" step="0.01" id="temperature" name="temperature" value="<?= old('temperature', '0.7') ?>" oninput="document.getElementById('temperature-value').textContent = this.value">
                        <div class="form-text">Controla la creatividad del modelo (0 = determinista, 1 = más creativo).</div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label><br>
                        <div class="form-check form-switch">
                            <input type="hidden" name="status" value="inactive">
                            <input class="form-check-input" type="checkbox" id="status" name="status" value="active" <?= old('status', 'active') == 'active' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="status">
                                <span id="status-label">Active</span>
                            </label>
                        </div>
                        <?php if (session('errors.status')): ?>
                            <div class="invalid-feedback d-block"> <?= session('errors.status') ?> </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="system_prompt" class="form-label">System Prompt <span class="text-danger">*</span></label>
<textarea class="form-control <?= session('errors.system_prompt') ? 'is-invalid' : '' ?>" 
          id="system_prompt" name="system_prompt" rows="8" required><?= old('system_prompt') ?></textarea>
<?php if (session('errors.system_prompt')): ?>
    <div class="invalid-feedback"><?= session('errors.system_prompt') ?></div>
<?php endif; ?>
                        <?php if (session('errors.prompt')): ?>
                            <div class="invalid-feedback"><?= session('errors.prompt') ?></div>
                        <?php endif; ?>
                        <div class="form-text">System instructions for the model that define its behavior</div>
                    </div>
                </div>
            </div>

            <!-- Checkbox para auto_create_api_users -->
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="auto_create_api_users" id="auto_create_api_users" value="1"
                        <?= old('auto_create_api_users', 0) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="auto_create_api_users">
                        Crear usuarios API automáticamente si no existen
                    </label>
                </div>
                <div class="form-text">Si está activado, los usuarios API se crearán automáticamente al usarse por primera vez con este botón.</div>
            </div>
            <div class="d-flex justify-content-between mt-4">
                <a href="<?= site_url('buttons') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Button</button>
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