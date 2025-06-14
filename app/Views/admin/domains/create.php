<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h1>Agregar Dominio al Tenant: <?= esc($tenant['name']) ?></h1>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h5>Agregar Dominio</h5>
        </div>
        <div class="card-body">
            <form action="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/domains/store') ?>" method="post">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="domain" class="form-label">Dominio</label>
                    <input type="text" class="form-control" id="domain" name="domain" value="<?= old('domain') ?>" required placeholder="ejemplo.com">
                    <div class="form-text">
                        Ingrese el dominio que desea asociar a este tenant. Ejemplo: mitienda.com
                    </div>
                </div>
                <div class="mb-3">
                    <div class="alert alert-info">
                        <p><strong>Nota:</strong> El dominio deberá ser verificado tras su registro.</p>
                        <p>La verificación puede tardar unos minutos según la propagación DNS.</p>
                    </div>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/domains') ?>" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
