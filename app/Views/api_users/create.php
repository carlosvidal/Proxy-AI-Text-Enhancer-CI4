<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('api-users') ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to API Users
        </a>
        <h2>Create New API User</h2>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-user-plus me-1"></i>
        Create New API User
    </div>
    <div class="card-body">
        <?php if (session()->has('error')): ?>
            <div class="alert alert-danger">
                <?= session('error') ?>
            </div>
        <?php endif; ?>

        <form action="<?= site_url('api-users/store') ?>" method="post">
            <?= csrf_field() ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">User Name</label>
                        <input type="text" class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>" 
                               id="name" name="name" value="<?= old('name') ?>">
                        <?php if (session('errors.name')): ?>
                            <div class="invalid-feedback"><?= session('errors.name') ?></div>
                        <?php endif; ?>
                        <div class="form-text">Optional: Name for reference only</div>
                    </div>

                    <div class="mb-3">
                        <label for="external_id" class="form-label">External ID</label>
                        <input type="text" class="form-control <?= session('errors.external_id') ? 'is-invalid' : '' ?>" 
                               id="external_id" name="external_id" value="<?= old('external_id') ?>" required>
                        <?php if (session('errors.external_id')): ?>
                            <div class="invalid-feedback"><?= session('errors.external_id') ?></div>
                        <?php endif; ?>
                        <div class="form-text">Required: Unique identifier for this user (e.g. their user ID in your system)</div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control <?= session('errors.email') ? 'is-invalid' : '' ?>" 
                               id="email" name="email" value="<?= old('email') ?>">
                        <?php if (session('errors.email')): ?>
                            <div class="invalid-feedback"><?= session('errors.email') ?></div>
                        <?php endif; ?>
                        <div class="form-text">Optional: Email for reference only</div>
                    </div>

                    <div class="mb-3">
                        <label for="quota" class="form-label">Monthly Token Quota</label>
                        <input type="number" class="form-control <?= session('errors.quota') ? 'is-invalid' : '' ?>" 
                               id="quota" name="quota" value="<?= old('quota', 100000) ?>" required>
                        <?php if (session('errors.quota')): ?>
                            <div class="invalid-feedback"><?= session('errors.quota') ?></div>
                        <?php endif; ?>
                        <div class="form-text">Maximum number of tokens this user can consume per month</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Button Access</label>
                        <div class="form-text mb-2">
                            Select which buttons this user can access
                        </div>
                        <?php if (!empty($buttons)): ?>
                            <?php foreach ($buttons as $button): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           name="button_access[]" value="<?= $button['button_id'] ?>" 
                                           id="button_<?= $button['button_id'] ?>"
                                           <?= in_array($button['button_id'], old('button_access', [])) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="button_<?= $button['button_id'] ?>">
                                        <?= esc($button['name']) ?> 
                                        <small class="text-muted">(<?= esc($button['domain']) ?>)</small>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                No buttons available. Please create at least one button first.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="<?= site_url('api-users') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create API User</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
