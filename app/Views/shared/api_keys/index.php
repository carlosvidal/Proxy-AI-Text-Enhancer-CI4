<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>API Keys para <?= esc($tenant['name']) ?></h2>
        <p class="text-muted">Gestiona las API Keys para tus integraciones con LLM Providers</p>
    </div>
    <?php if (isset($tenant['max_api_keys']) && count($apiKeys) < $tenant['max_api_keys']): ?>
    <div>
        <a href="<?= site_url('api-keys/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i>Agregar API Key
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-key me-1"></i>
        API Keys
    </div>
    <div class="card-body">
        <?php if (empty($apiKeys)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-1"></i>
                No tienes API Keys configuradas. Agrega una para comenzar a usar los servicios de LLM.
            </div>
            <div class="text-center py-3">
                <a href="<?= site_url('api-keys/create') ?>" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i>Agregar API Key
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Proveedor</th>
                            <th>API Key</th>
                            <th>Predeterminada</th>
                            <th>Creada</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apiKeys as $key): ?>
                            <tr>
                                <td><?= esc($key['name']) ?></td>
                                <td>
                                    <?php if (isset($providers[$key['provider']])): ?>
                                        <span class="badge bg-primary"><?= esc($providers[$key['provider']]) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= esc($key['provider']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code>••••••••••<?= substr(base64_decode($key['api_key']), -4) ?></code>
                                </td>
                                <td>
                                    <?php if ($key['is_default']): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Predeterminada</span>
                                    <?php else: ?>
                                        <a href="<?= site_url('api-keys/set-default/' . $key['id']) ?>" class="btn btn-sm btn-outline-primary">
                                            Establecer como predeterminada
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($key['created_at'])) ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $key['id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <!-- Delete Modal -->
                            <div class="modal fade" id="deleteModal<?= $key['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $key['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteModalLabel<?= $key['id'] ?>">Confirmar eliminación</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>¿Estás seguro de que deseas eliminar esta API Key?</p>
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
                                            <a href="<?= site_url('api-keys/delete/' . $key['id']) ?>" class="btn btn-danger">Eliminar</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-info-circle me-1"></i>
        Información sobre API Keys
    </div>
    <div class="card-body">
        <p>Las API Keys te permiten conectar tu cuenta con diferentes proveedores de LLM (Large Language Models).</p>
        
        <h5>Límites por plan:</h5>
        <ul>
            <li><strong>Plan Free:</strong> 1 API Key</li>
            <li><strong>Plan Pro:</strong> <?= isset($tenant['max_api_keys']) ? $tenant['max_api_keys'] : '5' ?> API Keys</li>
            <li><strong>Plan Enterprise:</strong> API Keys ilimitadas</li>
        </ul>
        
        <h5>Proveedores soportados:</h5>
        <div class="row">
            <?php foreach ($providers as $key => $name): ?>
            <div class="col-md-4 mb-2">
                <div class="d-flex align-items-center">
                    <span class="badge bg-primary me-2"><?= esc($name) ?></span>
                    <?php 
                    $hasKey = false;
                    foreach ($apiKeys as $apiKey) {
                        if ($apiKey['provider'] == $key) {
                            $hasKey = true;
                            break;
                        }
                    }
                    ?>
                    <?php if ($hasKey): ?>
                        <i class="fas fa-check-circle text-success"></i>
                    <?php else: ?>
                        <i class="fas fa-times-circle text-danger"></i>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
