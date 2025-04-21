<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= site_url('admin/tenants/view/' . $tenant['tenant_id']) ?>" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i><?= lang('App.tenant_users_back') ?>
        </a>
        <h2><?= sprintf(lang('App.tenant_users_title'), esc($tenant['name'])) ?></h2>
    </div>
    <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/users/create') ?>" class="btn btn-primary">
        <i class="fas fa-user-plus me-1"></i><?= lang('App.tenant_users_add') ?>
    </a>
</div>

<?php if (session()->has('error')): ?>
    <div class="alert alert-danger"><?= session('error') ?></div>
<?php endif; ?>

<?php if (session()->has('success')): ?>
    <div class="alert alert-success"><?= session('success') ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="alert alert-info"><?= lang('App.tenant_users_none') ?></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><?= lang('App.tenant_users_id') ?></th>
                            <th><?= lang('App.tenant_users_name') ?></th>
                            <th><?= lang('App.tenant_users_email') ?></th>
                            <th><?= lang('App.tenant_users_quota') ?></th>
                            <th><?= lang('App.tenant_users_status') ?></th>
                            <th><?= lang('App.tenant_users_actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= esc($user['user_id']) ?></td>
                                <td><?= esc($user['name'] ?? 'N/A') ?></td>
                                <td><?= esc($user['email'] ?? 'N/A') ?></td>
                                <td><?= number_format($user['quota']) ?></td>
                                <td>
                                    <?php if (isset($user['active']) && $user['active']): ?>
                                        <span class="badge bg-success"><?= lang('App.tenant_users_active') ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><?= lang('App.tenant_users_inactive') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/users/' . $user['user_id'] . '/edit') ?>" 
                                           class="btn btn-sm btn-outline-primary" title="<?= lang('App.tenant_users_edit') ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/users/' . $user['user_id'] . '/usage') ?>" 
                                           class="btn btn-sm btn-outline-info" title="<?= lang('App.tenant_users_usage') ?>">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger"
                                                title="<?= lang('App.tenant_users_delete') ?>"
                                                onclick="confirmDelete('<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/users/' . $user['user_id'] . '/delete') ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(url) {
    if (confirm('<?= lang('App.tenant_users_confirm_delete') ?>')) {
        window.location.href = url;
    }
}
</script>

<?= $this->endSection() ?>