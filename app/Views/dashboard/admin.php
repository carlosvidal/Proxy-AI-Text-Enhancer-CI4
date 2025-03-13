<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Panel de Administración</h1>
        </div>
    </div>

    <div class="row">
        <!-- Estadísticas -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Estadísticas Globales</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-2">Total Tenants</h6>
                                    <p class="h2 mb-0"><?= $stats['total_tenants'] ?? 0 ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-2">Total Solicitudes</h6>
                                    <p class="h2 mb-0"><?= number_format($stats['total_requests'] ?? 0) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones Rápidas -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Acciones Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="/tenants/create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Crear Nuevo Tenant
                        </a>
                        <a href="/tenants" class="btn btn-outline-primary">
                            <i class="fas fa-users"></i> Gestionar Tenants
                        </a>
                        <a href="/plans" class="btn btn-outline-primary">
                            <i class="fas fa-tags"></i> Gestionar Planes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actividad Reciente -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actividad Reciente</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tenant</th>
                                    <th>Plan</th>
                                    <th>Solicitudes</th>
                                    <th>Último Acceso</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($recent_activity) && !empty($recent_activity)): ?>
                                    <?php foreach ($recent_activity as $activity): ?>
                                        <tr>
                                            <td><?= esc($activity['name']) ?></td>
                                            <td><?= esc($activity['plan_name']) ?></td>
                                            <td><?= number_format($activity['requests']) ?></td>
                                            <td><?= $activity['last_access'] ?></td>
                                            <td>
                                                <?php if ($activity['active']): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No hay actividad reciente</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
