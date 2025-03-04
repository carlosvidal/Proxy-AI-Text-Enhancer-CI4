<div class="card">
    <div class="card-header">
        <i class="fas fa-edit me-1"></i>
        Edit Tenant
    </div>
    <div class="card-body">
        <form action="<?= site_url('tenants/edit/' . $tenant['id']) ?>" method="post">
            <?= csrf_field() ?>

            <?php if (isset($validation)): ?>
                <div class="alert alert-danger">
                    <?= $validation->listErrors() ?>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="name" class="form-label">Tenant Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= set_value('name', $tenant['name']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Contact Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= set_value('email', $tenant['email']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="quota" class="form-label">Default Quota (tokens)</label>
                <input type="number" class="form-control" id="quota" name="quota" value="<?= set_value('quota', $tenant['quota']) ?>" required>
                <div class="form-text">Default token quota for this tenant's users</div>
            </div>

            <div class="mb-3">
                <label for="active" class="form-label">Status</label>
                <select class="form-select" id="active" name="active">
                    <option value="1" <?= $tenant['active'] ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= !$tenant['active'] ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= site_url('tenants/view/' . $tenant['id']) ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Tenant</button>
            </div>
        </form>
    </div>
</div>