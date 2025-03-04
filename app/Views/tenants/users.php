<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('tenants/view/' . $tenant['id']) ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Tenant
        </a>
        <h2>Users for <?= esc($tenant['name']) ?></h2>
    </div>
    <a href="<?= site_url('tenants/add_user/' . $tenant['id']) ?>" class="btn btn-primary">
        <i class="fas fa-user-plus me-1"></i>Add User
    </a>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-users me-1"></i>
        User List
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
            <p class="text-muted text-center">No users found for this tenant.</p>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Quota</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= esc($user->user_id) ?></td>
                            <td><?= esc($user->name) ?></td>
                            <td><?= esc($user->email) ?></td>
                            <td><?= number_format($user->quota) ?></td>
                            <td>
                                <?php if ($user->active): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('Y-m-d', strtotime($user->created_at)) ?></td>
                            <td>
                                <a href="<?= site_url('tenants/edit_user/' . $user->id) ?>" class="btn btn-sm btn-warning text-white" title="Edit User">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= site_url('tenants/delete_user/' . $user->id) ?>" class="btn btn-sm btn-danger" title="Delete User" onclick="return confirm('Are you sure you want to delete this user?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>