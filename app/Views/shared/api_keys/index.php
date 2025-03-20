<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4">
    <h1 class="mt-4">API Keys para <?= esc($tenant['name']) ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= site_url('/dashboard') ?>">Dashboard</a></li>
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
                <i class="fas fa-key me-1"></i>
                API Keys
            </div>
            <?php if (isset($tenant['max_api_keys']) && count($apiKeys) < $tenant['max_api_keys']): ?>
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
                            <th>Predeterminada</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apiKeys as $key) : ?>
                            <tr>
                                <td><?= esc($key['name']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $key['provider'] === 'openai' ? 'success' : 'primary' ?>">
                                        <?= ucfirst(esc($key['provider'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="password" class="form-control" value="<?= esc($key['api_key']) ?>" readonly>
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($key['is_default']) : ?>
                                        <span class="badge bg-success">Predeterminada</span>
                                    <?php else : ?>
                                        <a href="<?= site_url('api-keys/set-default/' . $key['api_key_id']) ?>" class="btn btn-sm btn-outline-primary">
                                            Establecer como predeterminada
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteApiKeyModal<?= $key['api_key_id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Delete API Key Modal -->
                            <div class="modal fade" id="deleteApiKeyModal<?= $key['api_key_id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Eliminar API Key</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>¿Estás seguro de que deseas eliminar esta API Key?</p>
                                            <p><strong>Nombre:</strong> <?= esc($key['name']) ?></p>
                                            <p><strong>Proveedor:</strong> <?= ucfirst(esc($key['provider'])) ?></p>
                                            <?php if ($key['is_default']): ?>
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                Esta es la API Key predeterminada para <?= esc($providers[$key['provider']]) ?>. 
                                                Si la eliminas, se establecerá otra como predeterminada si está disponible.
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <a href="<?= site_url('api-keys/delete/' . $key['api_key_id']) ?>" class="btn btn-danger">Eliminar</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add API Key Modal -->
<div class="modal fade" id="addApiKeyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= site_url('api-keys/store') ?>" method="post">
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
                            <?php foreach ($providers as $key => $name): ?>
                            <option value="<?= $key ?>"><?= esc($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="api_key" class="form-label">API Key</label>
                        <input type="password" class="form-control" id="api_key" name="api_key" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Agregar API Key</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(function(button) {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
});
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
