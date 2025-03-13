<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4">
    <h1 class="mt-4">Create New Tenant</h1>
    
    <?php if (session()->has('error')): ?>
        <div class="alert alert-danger"><?= session('error') ?></div>
    <?php endif; ?>

    <?php if (session()->has('success')): ?>
        <div class="alert alert-success"><?= session('success') ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form action="<?= site_url('admin/tenants/create') ?>" method="post">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= old('name') ?>" required>
                    <?php if (session('errors.name')): ?>
                        <div class="invalid-feedback"><?= session('errors.name') ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="domain" class="form-label">Domain</label>
                    <input type="text" class="form-control <?= session('errors.domain') ? 'is-invalid' : '' ?>" id="domain" name="domain" value="<?= old('domain') ?>" required>
                    <?php if (session('errors.domain')): ?>
                        <div class="invalid-feedback"><?= session('errors.domain') ?></div>
                    <?php endif; ?>
                    <div class="form-text">Domain where the tenant's buttons will be used (e.g., example.com)</div>
                </div>

                <div class="mb-3">
                    <label for="api_quota" class="form-label">API Quota</label>
                    <input type="number" class="form-control <?= session('errors.api_quota') ? 'is-invalid' : '' ?>" id="api_quota" name="api_quota" value="<?= old('api_quota', 1000) ?>" required>
                    <?php if (session('errors.api_quota')): ?>
                        <div class="invalid-feedback"><?= session('errors.api_quota') ?></div>
                    <?php endif; ?>
                    <div class="form-text">Monthly API request limit</div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="active" name="active" value="1" <?= old('active', '1') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="active">Active</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Create Tenant</button>
                <a href="<?= site_url('admin/tenants') ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
