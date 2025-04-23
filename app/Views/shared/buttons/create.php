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
        <?php if (session()->has('error')): ?>
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
                        // Obtener dominios del tenant actual
                        $tenantsModel = new \App\Models\TenantsModel();
                        $tenant = $tenantsModel->find(session('tenant_id'));
                        $domains = $tenantsModel->getDomains(session('tenant_id'));
                        
                        if(empty($domains)): ?>
                            <div class="alert alert-warning">
                                No hay dominios configurados. Por favor, configure al menos un dominio.
                            </div>
                            <input type="text" class="form-control" name="domain" required 
                                   placeholder="Ingrese un dominio para este botón">
                        <?php else: ?>
                            <label class="form-label">Dominio Permitido</label>
                            <?php if(isset($tenant['max_domains']) && $tenant['max_domains'] > 1 && count($domains) > 1): ?>
                                <select name="domain" class="form-select" required>
                                    <?php foreach($domains as $domain): ?>
                                        <option value="<?= $domain['domain'] ?>">
                                            <?= $domain['domain'] ?>
                                            <?= isset($domain['verified']) && $domain['verified'] ? '' : ' (Pendiente de Verificación)' ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            <?php else: ?>
                                <input type="hidden" name="domain" value="<?= $domains[0]['domain'] ?>">
                                <input type="text" class="form-control" value="<?= $domains[0]['domain'] ?>" disabled>
                            <?php endif ?>
                        <?php endif ?>
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
                        <label for="prompt" class="form-label">System Prompt</label>
                        <textarea class="form-control <?= session('errors.prompt') ? 'is-invalid' : '' ?>" 
                                  id="prompt" name="prompt" rows="8"><?= old('prompt') ?></textarea>
                        <?php if (session('errors.prompt')): ?>
                            <div class="invalid-feedback"><?= session('errors.prompt') ?></div>
                        <?php endif; ?>
                        <div class="form-text">System instructions for the model that define its behavior</div>
                    </div>
                </div>
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