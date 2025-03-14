<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Create API User</h2>
        <p class="text-muted">Create a new API user with specific button access</p>
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

            <!-- External ID -->
            <div class="mb-3">
                <label for="external_id" class="form-label">External ID <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="text" 
                           class="form-control <?= session('errors.external_id') ? 'is-invalid' : '' ?>" 
                           id="external_id" 
                           name="external_id" 
                           value="<?= old('external_id') ?>"
                           maxlength="255"
                           placeholder="Enter the external identifier"
                           required>
                </div>
                <div class="form-text">
                    Required. Enter the identifier that will be used to consume the API.
                </div>
                <?php if (session('errors.external_id')): ?>
                    <div class="invalid-feedback">
                        <?= session('errors.external_id') ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Name -->
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" 
                       class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>" 
                       id="name" 
                       name="name" 
                       value="<?= old('name') ?>"
                       placeholder="Enter a descriptive name">
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
                <?php if (session('errors.email')): ?>
                    <div class="invalid-feedback">
                        <?= session('errors.email') ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Monthly Token Quota -->
            <div class="mb-3">
                <label for="quota" class="form-label">Monthly Token Quota <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" 
                           class="form-control <?= session('errors.quota') ? 'is-invalid' : '' ?>" 
                           id="quota" 
                           name="quota" 
                           value="<?= old('quota', 100000) ?>"
                           min="1"
                           required>
                    <span class="input-group-text">tokens</span>
                </div>
                <?php if (session('errors.quota')): ?>
                    <div class="invalid-feedback">
                        <?= session('errors.quota') ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Button Access -->
            <div class="mb-3">
                <label class="form-label">Button Access <span class="text-danger">*</span></label>
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($buttons)): ?>
                            <div class="alert alert-info mb-0">
                                No buttons available. Create some buttons first.
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($buttons as $button): ?>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="buttons[]" 
                                                   value="<?= $button['button_id'] ?>" 
                                                   id="button_<?= $button['button_id'] ?>"
                                                   <?= in_array($button['button_id'], old('buttons', [])) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="button_<?= $button['button_id'] ?>">
                                                <?= esc($button['name']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (session('errors.buttons')): ?>
                                <div class="invalid-feedback d-block">
                                    <?= session('errors.buttons') ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= site_url('api-users') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create API User</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
