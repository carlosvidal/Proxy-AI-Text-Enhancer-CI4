<?= $this->extend('layouts/main') ?>

<?php $this->section('content') ?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Create API User</h1>
    
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user-plus me-1"></i>
                    Create New API User
                </div>
                <div class="card-body">
                    <?php if (session()->getFlashdata('errors')): ?>
                        <div class="alert alert-danger">
                            <h4>Please correct the following errors:</h4>
                            <ul class="mb-0">
                                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                                    <li><?= esc($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

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
                            <label for="external_id" class="form-label">External ID <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control <?= session('errors.external_id') ? 'is-invalid' : '' ?>" 
                                   id="external_id" 
                                   name="external_id" 
                                   value="<?= old('external_id') ?>"
                                   maxlength="255"
                                   required>
                            <div class="form-text">A unique identifier for this API user</div>
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
                            <label for="quota" class="form-label">Monthly Token Quota <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control <?= session('errors.quota') ? 'is-invalid' : '' ?>" 
                                   id="quota" 
                                   name="quota" 
                                   value="<?= old('quota', 1000) ?>"
                                   required
                                   min="1">
                            <div class="form-text">Number of tokens allowed per month</div>
                            <?php if (session('errors.quota')): ?>
                                <div class="invalid-feedback">
                                    <?= session('errors.quota') ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary">Create API User</button>
                        <a href="<?= site_url('api-users') ?>" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection() ?>
