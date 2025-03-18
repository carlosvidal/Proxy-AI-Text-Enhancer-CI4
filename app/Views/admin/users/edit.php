<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('admin/users') ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Volver al listado
        </a>
        <h2>Editar Usuario: <?= esc($user['name']) ?></h2>
    </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            <?php foreach (session()->getFlashdata('errors') as $field => $error): ?>
                <li><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-user-edit me-1"></i>
        Datos del Usuario
    </div>
    <div class="card-body">
        <form action="<?= site_url('admin/users/update/' . $user['id']) ?>" method="post">
            <?= csrf_field() ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="username" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" value="<?= old('username', $user['username']) ?>" required>
                        <div class="form-text">El nombre de usuario debe ser único y tener al menos 3 caracteres.</div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= old('email', $user['email']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <div class="form-text">Dejar en blanco para mantener la contraseña actual. La nueva contraseña debe tener al menos 6 caracteres.</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= old('name', $user['name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Rol <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="tenant" <?= old('role', $user['role']) === 'tenant' ? 'selected' : '' ?>>Usuario de Tenant</option>
                            <option value="superadmin" <?= old('role', $user['role']) === 'superadmin' ? 'selected' : '' ?>>Administrador</option>
                        </select>
                    </div>

                    <div class="mb-3 tenant-field">
                        <label for="tenant_id" class="form-label">Tenant</label>
                        <select class="form-select" id="tenant_id" name="tenant_id">
                            <option value="">Seleccione un tenant</option>
                            <?php foreach ($tenants as $tenant): ?>
                                <option value="<?= $tenant['tenant_id'] ?>" <?= old('tenant_id', $user['tenant_id']) === $tenant['tenant_id'] ? 'selected' : '' ?>>
                                    <?= esc($tenant['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Solo para usuarios con rol de Tenant.</div>
                    </div>

                    <div class="mb-3">
                        <label for="active" class="form-label">Estado</label>
                        <select class="form-select" id="active" name="active" required>
                            <option value="1" <?= old('active', $user['active']) == 1 ? 'selected' : '' ?>>Activo</option>
                            <option value="0" <?= old('active', $user['active']) == 0 ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
                <a href="<?= site_url('admin/users') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role');
        const tenantFields = document.querySelectorAll('.tenant-field');

        function toggleTenantFields() {
            const showFields = roleSelect.value === 'tenant';
            tenantFields.forEach(field => {
                field.style.display = showFields ? 'block' : 'none';

                // Si es campo requerido y es el tenant_id, toggle el atributo required
                const tenantIdField = field.querySelector('#tenant_id');
                if (tenantIdField) {
                    if (showFields) {
                        tenantIdField.setAttribute('required', 'required');
                    } else {
                        tenantIdField.removeAttribute('required');
                    }
                }
            });
        }

        // Inicializar y añadir listener para cambios
        toggleTenantFields();
        roleSelect.addEventListener('change', toggleTenantFields);
    });
</script>

<?= $this->endSection() ?>