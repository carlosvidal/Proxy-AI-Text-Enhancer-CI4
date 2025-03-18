<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Gestión de Usuarios de Autenticación</h2>
    <a href="<?= site_url('admin/users/create') ?>" class="btn btn-primary">
        <i class="fas fa-user-plus me-2"></i>Crear Usuario
    </a>
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

<div class="card">
    <div class="card-header">
        <i class="fas fa-users me-1"></i>
        Listado de Usuarios
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
            <p class="text-muted text-center">No se encontraron usuarios en el sistema.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Tenant</th>
                            <th>Estado</th>
                            <th>Último Login</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= esc($user['name']) ?></td>
                                <td><?= esc($user['username']) ?></td>
                                <td><?= esc($user['email']) ?></td>
                                <td>
                                    <?php if ($user['role'] === 'superadmin'): ?>
                                        <span class="badge bg-danger">Admin</span>
                                    <?php elseif ($user['role'] === 'tenant'): ?>
                                        <span class="badge bg-primary">Tenant</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= esc($user['role']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($user['tenant_id'])): ?>
                                        <?= esc($user['tenant_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sin tenant</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['active']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($user['last_login'])): ?>
                                        <?= date('d/m/Y H:i', strtotime($user['last_login'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Nunca</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?= site_url('admin/users/view/' . $user['id']) ?>" class="btn btn-sm btn-outline-info" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= site_url('admin/users/edit/' . $user['id']) ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['id'] != session()->get('id')): ?>
                                            <a href="<?= site_url('admin/users/delete/' . $user['id']) ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('¿Está seguro que desea eliminar este usuario?')"
                                                title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>