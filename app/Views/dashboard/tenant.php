<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Panel de Control</h1>
        </div>
    </div>

    <div class="row">
        <!-- Botones de IA -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Mis Botones de IA</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <!-- Los botones se cargarán dinámicamente -->
                    </div>
                    <button class="btn btn-primary mt-3" id="createButton">
                        <i class="fas fa-plus"></i> Crear Nuevo Botón
                    </button>
                </div>
            </div>
        </div>

        <!-- Estadísticas de Uso -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Uso del Servicio</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Cuota Mensual</h6>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">0 de 1,000 solicitudes</small>
                    </div>
                    <div>
                        <h6>Usuarios Activos</h6>
                        <p class="h3">0</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
