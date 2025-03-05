<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <?php if (isset($tenant['id'])): ?>
            <a href="<?= site_url('tenants/users/' . $tenant['id']) ?>" class="btn btn-secondary btn-sm mb-2">
                <i class="fas fa-arrow-left me-1"></i>Volver a Usuarios
            </a>
        <?php else: ?>
            <a href="<?= site_url('tenants/users/1') ?>" class="btn btn-secondary btn-sm mb-2">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
        <?php endif; ?>
        <h2>Uso de Tokens: <?= esc($user['name']) ?> <span class="badge badge-user"><?= esc($user['user_id']) ?></span></h2>
    </div>

    <?php if (session()->get('role') === 'admin'): ?>
        <form action="<?= site_url('users/reset-usage/' . $tenant_id . '/' . $user['user_id']) ?>" method="post"
            onsubmit="return confirm('¿Estás seguro? Esta acción eliminará todos los registros de uso de este usuario.')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash me-1"></i>Restablecer Uso
            </button>
        </form>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-info-circle me-1"></i>
                Información de Cuota
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Cuota Total</label>
                    <h3><?= number_format($quota['total_quota']) ?> tokens</h3>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tokens Usados</label>
                    <h3><?= number_format($total_used) ?> tokens</h3>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tokens Restantes</label>
                    <h3 class="<?= $remaining < 0 ? 'text-danger' : '' ?>"><?= number_format($remaining) ?> tokens</h3>
                </div>

                <div class="mb-3">
                    <label class="form-label">Periodo de Reinicio</label>
                    <h3><?= ucfirst($quota['reset_period'] ?? 'Monthly') ?></h3>
                </div>

                <div>
                    <label class="form-label">Uso Actual</label>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar <?= $percentage > 90 ? 'bg-danger' : ($percentage > 70 ? 'bg-warning' : 'bg-success') ?>"
                            role="progressbar" style="width: <?= $percentage ?>%;"
                            aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100">
                            <?= round($percentage) ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-line me-1"></i>
                Uso de Tokens (Últimos 14 días)
            </div>
            <div class="card-body">
                <?php if ($total_used == 0): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Este usuario no tiene registros de uso de tokens en los últimos 14 días.
                    </div>
                <?php endif; ?>
                <canvas id="usageChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-history me-1"></i>
        Historial de Uso
    </div>
    <div class="card-body">
        <?php if (empty($usage_logs)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No hay registros de uso para este usuario en los últimos 30 días.
            </div>
            <p class="text-center text-muted">
                Los registros de uso aparecerán aquí una vez que el usuario comience a utilizar el servicio.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>Modelo</th>
                            <th>Tokens</th>
                            <th>Imagen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usage_logs as $log): ?>
                            <tr>
                                <td><?= date('Y-m-d H:i:s', strtotime($log['usage_date'])) ?></td>
                                <td><span class="badge badge-provider"><?= esc($log['provider']) ?></span></td>
                                <td><span class="badge badge-model"><?= esc($log['model']) ?></span></td>
                                <td><?= number_format($log['tokens']) ?></td>
                                <td><?= $log['has_image'] ? '<span class="badge bg-info">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gráfico de uso
        const ctx = document.getElementById('usageChart').getContext('2d');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Tokens Usados',
                    data: <?= json_encode($chart_data) ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Tokens'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Fecha'
                        }
                    }
                }
            }
        });
    });
</script>

<?= $this->endSection() ?>