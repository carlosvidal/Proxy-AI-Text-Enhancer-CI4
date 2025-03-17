<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Tenants List -->
<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><?= lang('App.tenants_manage') ?></h2>
            <a href="<?= site_url('admin/tenants/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i><?= lang('App.tenants_add') ?>
            </a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <i class="fas fa-building me-1"></i>
                <?= lang('App.tenants_list') ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?= lang('App.tenants_name') ?></th>
                                <th><?= lang('App.tenants_email') ?></th>
                                <th><?= lang('App.tenants_api_users') ?></th>
                                <th><?= lang('App.tenants_status') ?></th>
                                <th><?= lang('App.tenants_subscription') ?></th>
                                <th><?= lang('App.tenants_usage') ?></th>
                                <th><?= lang('App.tenants_created') ?></th>
                                <th><?= lang('App.tenants_actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tenants as $tenant): ?>
                            <tr>
                                <td>
                                    <a href="<?= site_url('admin/tenants/view/' . $tenant['tenant_id']) ?>" class="text-decoration-none">
                                        <?= esc($tenant['name']) ?>
                                    </a>
                                </td>
                                <td><?= esc($tenant['email'] ?? 'N/A') ?></td>
                                <td>
                                    <a href="<?= site_url('admin/tenants/' . $tenant['tenant_id'] . '/users') ?>" class="text-decoration-none">
                                        <?= $tenant['api_users'] ?? 0 ?> <?= lang('App.tenants_users') ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($tenant['active']): ?>
                                        <span class="badge bg-success"><?= lang('App.tenants_active') ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><?= lang('App.tenants_inactive') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status = strtolower($tenant['subscription_status'] ?? 'trial');
                                    if ($status === 'trial') {
                                        $statusClass = 'bg-info';
                                    } elseif ($status === 'active') {
                                        $statusClass = 'bg-success';
                                    } elseif ($status === 'expired') {
                                        $statusClass = 'bg-danger';
                                    } else {
                                        $statusClass = 'bg-secondary';
                                    }
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= lang('App.tenants_subscription_' . $status) ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="d-block text-muted">
                                        <?= number_format($tenant['total_requests'] ?? 0) ?> <?= lang('App.tenants_requests') ?>
                                    </small>
                                    <small class="d-block text-muted">
                                        <?= number_format($tenant['total_tokens'] ?? 0) ?> <?= lang('App.tenants_tokens') ?>
                                    </small>
                                </td>
                                <td><?= date('M j, Y', strtotime($tenant['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?= site_url('admin/tenants/view/' . $tenant['tenant_id']) ?>" 
                                           class="btn btn-sm btn-outline-info" 
                                           title="<?= lang('App.tenants_view') ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= site_url('admin/tenants/edit/' . $tenant['tenant_id']) ?>" 
                                           class="btn btn-sm btn-outline-primary"
                                           title="<?= lang('App.tenants_edit') ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= site_url('admin/tenants/delete/' . $tenant['tenant_id']) ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('<?= lang('App.tenants_delete_confirm') ?>')"
                                           title="<?= lang('App.tenants_delete') ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($tenants)): ?>
                            <tr>
                                <td colspan="8" class="text-center"><?= lang('App.tenants_no_tenants') ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
