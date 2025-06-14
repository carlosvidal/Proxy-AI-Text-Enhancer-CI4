<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Dominios del Tenant: <?= esc($tenant['name']) ?></h1>
        </div>
        <div class="col-md-6 text-end">
            <?php if (count($domains) < $tenant['max_domains']): ?>
                <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/domains/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Agregar Dominio
                </a>
            <?php else: ?>
                <button class="btn btn-secondary" disabled title="Límite alcanzado">
                    <i class="bi bi-plus-circle"></i> Límite alcanzado (<?= $tenant['max_domains'] ?>)
                </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h5>Dominios registrados</h5>
                </div>
                <div class="col-md-6 text-end">
                    <span class="badge bg-info">
                        <?= count($domains) ?> / <?= $tenant['max_domains'] ?> permitidos
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($domains)): ?>
                <div class="alert alert-info">
                    No hay dominios registrados para este tenant.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Dominio</th>
                                <th>Estado</th>
                                <th>Fecha de registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($domains as $domain): ?>
                                <tr>
                                    <td><?= esc($domain['domain']) ?></td>
                                    <td>
                                        <?php if ($domain['verified']): ?>
                                            <span class="badge bg-success">Verificado</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($domain['created_at']) ?></td>
                                    <td>
                                        <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/domains/delete/' . $domain['domain_id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este dominio?')">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
