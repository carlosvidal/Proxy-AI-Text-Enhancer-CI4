<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4">
    <h1 class="mt-4"><?= lang('App.api_users_create') ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= site_url('/dashboard') ?>"><?= lang('App.nav_dashboard') ?></a></li>
        <li class="breadcrumb-item"><a href="<?= site_url('/api-users') ?>"><?= lang('App.api_users_title') ?></a></li>
        <li class="breadcrumb-item active"><?= lang('App.api_users_create') ?></li>
    </ol>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user-plus me-1"></i>
            <?= lang('App.api_users_create') ?>
        </div>
        <div class="card-body">
            <form action="<?= site_url('api-users/store') ?>" method="post">
                <?= csrf_field() ?>

                <?php if (session()->get('is_admin')): ?>
                <!-- Tenant Selection for Super Admin -->
                <div class="mb-3">
                    <label for="tenant_id" class="form-label">Tenant <span class="text-danger">*</span></label>
                    <select class="form-select <?= session('errors.tenant_id') ? 'is-invalid' : '' ?>" 
                            id="tenant_id" 
                            name="tenant_id" 
                            required>
                        <option value="">Select Tenant</option>
                        <?php foreach ($tenants as $tenant): ?>
                        <option value="<?= esc($tenant['tenant_id']) ?>" <?= old('tenant_id') == $tenant['tenant_id'] ? 'selected' : '' ?>>
                            <?= esc($tenant['name']) ?> (<?= esc($tenant['tenant_id']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (session('errors.tenant_id')): ?>
                        <div class="invalid-feedback">
                            <?= session('errors.tenant_id') ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="external_id" class="form-label"><?= lang('App.api_users_external_id') ?></label>
                    <input type="text" 
                           class="form-control <?= session('errors.external_id') ? 'is-invalid' : '' ?>" 
                           id="external_id" 
                           name="external_id" 
                           value="<?= old('external_id') ?>"
                           maxlength="255"
                           required>
                    <div class="form-text"><?= lang('App.api_users_external_id_help') ?></div>
                    <?php if (session('errors.external_id')): ?>
                        <div class="invalid-feedback">
                            <?= session('errors.external_id') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>" 
                           id="name" 
                           name="name" 
                           value="<?= old('name') ?>"
                           maxlength="255"
                           required>
                    <div class="form-text">A descriptive name to identify this API user</div>
                    <?php if (session('errors.name')): ?>
                        <div class="invalid-feedback">
                            <?= session('errors.name') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" 
                           class="form-control <?= session('errors.email') ? 'is-invalid' : '' ?>" 
                           id="email" 
                           name="email" 
                           value="<?= old('email') ?>">
                    <div class="form-text">Optional email address for notifications</div>
                    <?php if (session('errors.email')): ?>
                        <div class="invalid-feedback">
                            <?= session('errors.email') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="quota" class="form-label"><?= lang('App.api_users_quota') ?></label>
                    <input type="number" 
                           class="form-control <?= session('errors.quota') ? 'is-invalid' : '' ?>" 
                           id="quota" 
                           name="quota" 
                           value="<?= old('quota', 1000) ?>"
                           required
                           min="1">
                    <div class="form-text"><?= lang('App.api_users_quota_help') ?></div>
                    <?php if (session('errors.quota')): ?>
                        <div class="invalid-feedback">
                            <?= session('errors.quota') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="daily_quota" class="form-label"><?= lang('App.api_users_daily_quota') ?></label>
                    <input type="number" 
                           class="form-control <?= session('errors.daily_quota') ? 'is-invalid' : '' ?>" 
                           id="daily_quota" 
                           name="daily_quota" 
                           value="<?= old('daily_quota', 10000) ?>"
                           required
                           min="1">
                    <div class="form-text"><?= lang('App.api_users_daily_quota_help') ?></div>
                    <?php if (session('errors.daily_quota')): ?>
                        <div class="invalid-feedback">
                            <?= session('errors.daily_quota') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="active" name="active" 
                               value="1" <?= old('active', true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="active"><?= lang('App.api_users_active') ?></label>
                    </div>
                    <div class="form-text"><?= lang('App.api_users_active_help') ?></div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= site_url('api-users') ?>" class="btn btn-secondary">
                        <?= lang('App.common_cancel') ?>
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <?= lang('App.common_create') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
