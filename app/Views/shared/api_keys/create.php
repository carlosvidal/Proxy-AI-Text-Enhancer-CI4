<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Agregar API Key</h2>
        <p class="text-muted">Agrega una nueva API Key para conectar con proveedores de LLM</p>
    </div>
    <div>
        <a href="<?= site_url('api-keys') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver a API Keys
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-key me-1"></i>
        Nueva API Key
    </div>
    <div class="card-body">
        <?= form_open('api-keys/store') ?>
            <div class="mb-3">
                <label for="name" class="form-label">Nombre de la API Key</label>
                <input type="text" class="form-control <?= session('validation') && session('validation')->hasError('name') ? 'is-invalid' : '' ?>" 
                       id="name" name="name" value="<?= old('name') ?>" required>
                <div class="form-text">Asigna un nombre descriptivo para identificar esta API Key</div>
                <?php if (session('validation') && session('validation')->hasError('name')): ?>
                    <div class="invalid-feedback">
                        <?= session('validation')->getError('name') ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="provider" class="form-label">Proveedor</label>
                <select class="form-select <?= session('validation') && session('validation')->hasError('provider') ? 'is-invalid' : '' ?>" 
                        id="provider" name="provider" required>
                    <option value="" selected disabled>Selecciona un proveedor</option>
                    <?php foreach ($providers as $key => $name): ?>
                        <option value="<?= $key ?>" <?= old('provider') == $key ? 'selected' : '' ?>><?= esc($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Selecciona el proveedor de LLM para esta API Key</div>
                <?php if (session('validation') && session('validation')->hasError('provider')): ?>
                    <div class="invalid-feedback">
                        <?= session('validation')->getError('provider') ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="api_key" class="form-label">API Key</label>
                <div class="input-group">
                    <input type="password" class="form-control <?= session('validation') && session('validation')->hasError('api_key') ? 'is-invalid' : '' ?>" 
                           id="api_key" name="api_key" value="<?= old('api_key') ?>" required>
                    <button class="btn btn-outline-secondary" type="button" id="toggleApiKey">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="form-text">Ingresa la API Key proporcionada por el proveedor. Esta se almacenará de forma segura.</div>
                <?php if (session('validation') && session('validation')->hasError('api_key')): ?>
                    <div class="invalid-feedback">
                        <?= session('validation')->getError('api_key') ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_default" name="is_default" value="1" <?= old('is_default') ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_default">Establecer como API Key predeterminada para este proveedor</label>
                <div class="form-text">Si es la primera API Key para este proveedor, se establecerá automáticamente como predeterminada.</div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Guardar API Key
                </button>
            </div>
        <?= form_close() ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-info-circle me-1"></i>
        Información sobre API Keys
    </div>
    <div class="card-body">
        <h5>¿Dónde obtener API Keys?</h5>
        <ul>
            <li><strong>OpenAI:</strong> <a href="https://platform.openai.com/api-keys" target="_blank">https://platform.openai.com/api-keys</a></li>
            <li><strong>Anthropic:</strong> <a href="https://console.anthropic.com/settings/keys" target="_blank">https://console.anthropic.com/settings/keys</a></li>
            <li><strong>Cohere:</strong> <a href="https://dashboard.cohere.com/api-keys" target="_blank">https://dashboard.cohere.com/api-keys</a></li>
            <li><strong>Mistral AI:</strong> <a href="https://console.mistral.ai/api-keys/" target="_blank">https://console.mistral.ai/api-keys/</a></li>
            <li><strong>DeepSeek:</strong> <a href="https://platform.deepseek.com/" target="_blank">https://platform.deepseek.com/</a></li>
            <li><strong>Google AI:</strong> <a href="https://makersuite.google.com/app/apikey" target="_blank">https://makersuite.google.com/app/apikey</a></li>
        </ul>
        
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-1"></i>
            <strong>Importante:</strong> Nunca compartas tus API Keys. Estas se almacenan de forma segura y encriptada en nuestra base de datos.
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleApiKey = document.getElementById('toggleApiKey');
    const apiKeyInput = document.getElementById('api_key');
    
    toggleApiKey.addEventListener('click', function() {
        const type = apiKeyInput.getAttribute('type') === 'password' ? 'text' : 'password';
        apiKeyInput.setAttribute('type', type);
        
        const icon = toggleApiKey.querySelector('i');
        if (type === 'text') {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
});
</script>

<?= $this->endSection() ?>
