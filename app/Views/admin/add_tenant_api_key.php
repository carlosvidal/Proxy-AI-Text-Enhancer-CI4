<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container-fluid px-4">
    <h1 class="mt-4">Agregar API Key</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= site_url('/admin/tenants/' . esc($tenantId)) ?>">Detalles del Tenant</a></li>
        <li class="breadcrumb-item"><a href="<?= site_url('/admin/tenants/' . esc($tenantId) . '/api_keys') ?>">API Keys</a></li>
        <li class="breadcrumb-item active">Agregar API Key</li>
    </ol>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-key me-1"></i> Nueva API Key para Tenant <b><?= esc($tenantId) ?></b>
            </div>
            <a href="<?= site_url('admin/tenants/' . esc($tenantId) . '/api_keys') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver a API Keys
            </a>
        </div>
        <div class="card-body">
            <form method="post" action="#">
                <div class="mb-3">
                    <label for="api_key_name" class="form-label">Nombre de la API Key</label>
                    <input type="text" class="form-control" id="api_key_name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="provider" class="form-label">Proveedor</label>
                    <select class="form-select" id="provider" name="provider" required>
                        <option value="openai">OpenAI</option>
                        <option value="anthropic">Anthropic</option>
                        <option value="cohere">Cohere</option>
                        <option value="mistral">Mistral</option>
                        <option value="deepseek">DeepSeek</option>
                        <option value="google">Google</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="api_key" class="form-label">API Key</label>
                    <input type="text" class="form-control" id="api_key" name="api_key" required>
                </div>
                <button type="submit" class="btn btn-primary">Guardar API Key</button>
            </form>
            <p class="mt-3"><i>(Este es un formulario de ejemplo. Aún no guarda datos.)</i></p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
