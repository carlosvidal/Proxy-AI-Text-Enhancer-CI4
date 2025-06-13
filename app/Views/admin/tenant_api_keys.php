<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container-fluid px-4">
    <h1 class="mt-4">API Keys para <?= esc($tenant['name']) ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= site_url('/admin/tenants/view/'.$tenant['tenant_id']) ?>">Detalles del Tenant</a></li>
        <li class="breadcrumb-item active">API Keys</li>
    </ol>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-key me-1"></i> API Keys del Tenant
            </div>
            <?php if (count($apiKeys) < $tenant['max_api_keys']): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addApiKeyModal">
                <i class="fas fa-plus me-1"></i> Agregar API Key
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Proveedor</th>
                            <th>API Key</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Predeterminada</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apiKeys as $key) : ?>
                            <tr>
                                <td><?= esc($key['name']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $key['provider'] === 'openai' ? 'success' : ($key['provider'] === 'anthropic' ? 'warning' : ($key['provider'] === 'mistral' ? 'info' : ($key['provider'] === 'cohere' ? 'secondary' : ($key['provider'] === 'deepseek' ? 'dark' : 'primary')))) ?>">
                                        <?= ucfirst(esc($key['provider'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                        $apiKey = esc($key['api_key']);
                                        $shortKey = strlen($apiKey) > 12 ? substr($apiKey, 0, 4) . '...' . substr($apiKey, -4) : $apiKey;
                                    ?>
                                    <span class="font-monospace"><?= $shortKey ?></span>
                                </td>
                                <td>
                                    <?php if ($key['active']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= isset($key['created_at']) ? date('Y-m-d', strtotime($key['created_at'])) : '' ?>
                                </td>
                                <td>
                                    <?php if ($key['is_default']): ?>
                                        <span class="badge bg-info">Predeterminada</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Acciones: activar/desactivar, eliminar, set default -->
                                    <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/api_keys/set_default/' . $key['api_key_id']) ?>" class="btn btn-outline-info btn-sm" title="Hacer predeterminada"><i class="fas fa-star"></i></a>
                                    <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/api_keys/toggle/' . $key['api_key_id']) ?>" class="btn btn-outline-warning btn-sm" title="Activar/Desactivar"><i class="fas fa-power-off"></i></a>
                                    <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/api_keys/delete/' . $key['api_key_id']) ?>" class="btn btn-outline-danger btn-sm" title="Eliminar"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Modal para agregar API Key -->
<div class="modal fade" id="addApiKeyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/api_keys/add') ?>" method="post">
                <input type="hidden" name="tenant_id" value="<?= esc($tenant['tenant_id']) ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar API Key</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="provider" class="form-label">Proveedor</label>
                        <select class="form-select" id="provider" name="provider" required>
                            <option value="">Seleccione Proveedor</option>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar API Key</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
