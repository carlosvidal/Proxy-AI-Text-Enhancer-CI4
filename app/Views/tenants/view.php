<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('tenants') ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to Tenants
        </a>
        <h2><?= esc($tenant['name']) ?> <span class="badge badge-tenant"><?= esc($tenant['id']) ?></span></h2>
    </div>
    <div>
        <a href="<?= site_url('tenants/edit/' . $tenant['id']) ?>" class="btn btn-warning text-white">
            <i class="fas fa-edit me-1"></i>Edit
        </a>
        <a href="<?= site_url('tenants/users/' . $tenant['id']) ?>" class="btn btn-primary">
            <i class="fas fa-users me-1"></i>Manage Users
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-info-circle me-1"></i>
                Tenant Information
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th style="width: 30%">Name:</th>
                        <td><?= esc($tenant['name']) ?></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?= esc($tenant['email']) ?></td>
                    </tr>
                    <tr>
                        <th>Default Quota:</th>
                        <td><?= number_format($tenant['quota']) ?> tokens</td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <?php if ($tenant['active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Created:</th>
                        <td><?= date('Y-m-d H:i', strtotime($tenant['created_at'])) ?></td>
                    </tr>
                    <?php if ($tenant['updated_at']): ?>
                        <tr>
                            <th>Last Updated:</th>
                            <td><?= date('Y-m-d H:i', strtotime($tenant['updated_at'])) ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-users me-1"></i>
                Users (<?= count($users) ?>)
            </div>
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <p class="text-muted text-center">No users found for this tenant.</p>
                    <div class="text-center mt-3">
                        <a href="<?= site_url('tenants/add_user/' . $tenant['id']) ?>" class="btn btn-primary">
                            <i class="fas fa-user-plus me-1"></i>Add User
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Name</th>
                                    <th>Quota</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= esc($user->user_id) ?></td>
                                        <td><?= esc($user->name) ?></td>
                                        <td><?= number_format($user->quota) ?></td>
                                        <td>
                                            <?php if ($user->active): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <a href="<?= site_url('tenants/users/' . $tenant['id']) ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-users me-1"></i>Manage All Users
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-chart-line me-1"></i>
        Usage Overview
    </div>
    <div class="card-body">
        <!-- Aquí se podría agregar un gráfico de uso por mes o alguna estadística relevante -->
        <p class="text-muted text-center">Usage statistics will be displayed here.</p>
    </div>
</div>

<?= $this->endSection() ?>