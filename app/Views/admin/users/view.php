<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('admin/users') ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Volver al listado
        </a>
        <h2>Detalles del Usuario: <?= esc($user['name']) ?></h2>
    </div>
    <div>
        <a href="<?= site_url('admin/users/edit/' . $user['id']) ?>" class="btn btn-primary">
            <i class="fas fa-edit me-1"></i>Editar
        </a>
        <?php if ($user['id'] != session()->get('id')): ?>
            <a href="<?= site_url('admin/users/delete/' . $user['id']) ?>"
                class="btn btn-danger"
                onclick="return confirm('¿Está seguro que desea eliminar este usuario?')">
                <i class="fas fa-trash me-1"></i>Eliminar
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <!-- Información básica del usuario -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user-circle me-1"></i>
                Información del Usuario
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th style="width: 30%">ID:</th>
                        <td><?= $user['id'] ?></td>
                    </tr>
                    <tr>
                        <th>Nombre:</th>
                        <td><?= esc($user['name']) ?></td>
                    </tr>
                    <tr>
                        <th>Usuario:</th>
                        <td><?= esc($user['username']) ?></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?= esc($user['email']) ?></td>
                    </tr>
                    <tr>
                        <th>Rol:</th>
                        <td>
                            <?php if ($user['role'] === 'superadmin'): ?>
                                <span class="badge bg-danger">Administrador</span>
                            <?php elseif ($user['role'] === 'tenant'): ?>
                                <span class="badge bg-primary">Usuario de Tenant</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= esc($user['role']) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Estado:</th>
                        <td>
                            <?php if ($user['active']): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Fecha de Registro:</th>
                        <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                    </tr>
                    <tr>
                        <th>Última Actualización:</th>
                        <td>
                            <?php if (!empty($user['updated_at'])): ?>
                                <?= date('d/m/Y H:i', strtotime($user['updated_at'])) ?>
                            <?php else: ?>
                                <span class="text-muted">Sin actualizar</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Último Acceso:</th>
                        <td>
                            <?php if (!empty($user['last_login'])): ?>
                                <?= date('d/m/Y H:i', strtotime($user['last_login'])) ?>
                            <?php else: ?>
                                <span class="text-muted">Nunca</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Información del tenant si aplica -->
        <?php if ($user['role'] === 'tenant'): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-building me-1"></i>
                    Información del Tenant
                </div>
                <div class="card-body">
                    <?php if ($tenant): ?>
                        <table class="table">
                            <tr>
                                <th style="width: 30%">Tenant ID:</th>
                                <td><?= esc($tenant['tenant_id']) ?></td>
                            </tr>
                            <tr>
                                <th>Nombre:</th>
                                <td><?= esc($tenant['name']) ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?= esc($tenant['email']) ?></td>
                            </tr>
                            <tr>
                                <th>Estado:</th>
                                <td>
                                    <?php if ($tenant['active']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                        <div class="mt-3">
                            <a href="<?= site_url('admin/tenants/view/' . $tenant['tenant_id']) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-external-link-alt me-1"></i>Ver Detalles del Tenant
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Este usuario tiene rol de tenant pero no está asignado a ningún tenant.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>