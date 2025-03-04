<div class="card">
    <div class="card-header">
        <i class="fas fa-plus-circle me-1"></i>
        Create New Tenant
    </div>
    <div class="card-body">
        <form action="<?= site_url('tenants/create') ?>" method="post">
            <?= csrf_field() ?>

            <?php if (isset($validation)): ?>
                <div class="alert alert-danger">
                    <?= $validation->listErrors() ?>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="name" class="form-label">Tenant Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= set_value('name') ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Contact Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= set_value('email') ?>" required>
            </div>

            <div class="mb-3">
                <label for="quota" class="form-label">Default Quota (tokens)</label>
                <input type="number" class="form-control" id="quota" name="quota" value="<?= set_value('quota', 100000) ?>" required>
                <div class="form-text">Default token quota for this tenant's users</div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= site_url('tenants') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Tenant</button>
            </div>
        </form>
    </div>
</div>