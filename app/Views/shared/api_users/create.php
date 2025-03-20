<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Create API User</h2>
        <p class="text-muted">Create a new API user to track API consumption</p>
    </div>
    <div>
        <a href="<?= site_url('api-users') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to API Users
        </a>
    </div>
</div>

<?php if (session()->has('error')): ?>
    <div class="alert alert-danger">
        <?= session('error') ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-user-plus me-1"></i>
        API User Details
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

            <!-- External ID -->
            <div class="mb-3">
                <label for="external_id" class="form-label">External ID <span class="text-danger">*</span></label>
                <input type="text" 
                       class="form-control <?= session('errors.external_id') ? 'is-invalid' : '' ?>" 
                       id="external_id" 
                       name="external_id" 
                       value="<?= old('external_id') ?>"
                       maxlength="255"
                       placeholder="Enter external system identifier"
                       required>
                <div class="form-text">
                    Required. Enter the identifier that will be used to map this API user to your external system's user.
                </div>
                <?php if (session('errors.external_id')): ?>
                    <div class="invalid-feedback">
                        <?= session('errors.external_id') ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Name -->
            <div class="mb-3">
                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" 
                       class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>" 
                       id="name" 
                       name="name" 
                       value="<?= old('name') ?>"
                       maxlength="255"
                       placeholder="Enter a descriptive name"
                       required>
                <div class="form-text">
                    Required. A descriptive name to identify this API user.
                </div>
                <?php if (session('errors.name')): ?>
                    <div class="invalid-feedback">
                        <?= session('errors.name') ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" 
                       class="form-control <?= session('errors.email') ? 'is-invalid' : '' ?>" 
                       id="email" 
                       name="email" 
                       value="<?= old('email') ?>"
                       placeholder="Enter contact email">
                <div class="form-text">
                    Optional. Contact email for notifications.
                </div>
                <?php if (session('errors.email')): ?>
                    <div class="invalid-feedback">
                        <?= session('errors.email') ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quota -->
            <div class="mb-3">
                <label for="quota" class="form-label">Monthly Token Quota <span class="text-danger">*</span></label>
                <input type="number" 
                       class="form-control <?= session('errors.quota') ? 'is-invalid' : '' ?>" 
                       id="quota" 
                       name="quota" 
                       value="<?= old('quota', 1000) ?>"
                       min="1"
                       required>
                <div class="form-text">
                    Required. Maximum number of tokens this user can consume per month.
                </div>
                <?php if (session('errors.quota')): ?>
                    <div class="invalid-feedback">
                        <?= session('errors.quota') ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Create API User
                </button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
