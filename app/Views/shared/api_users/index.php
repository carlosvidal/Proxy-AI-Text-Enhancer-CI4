<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4">
    <h1 class="mt-4"><?= lang('App.api_users_title') ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= site_url('/dashboard') ?>"><?= lang('App.nav_dashboard') ?></a></li>
        <li class="breadcrumb-item active"><?= lang('App.api_users_title') ?></li>
    </ol>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-users me-1"></i>
                    <?= lang('App.api_users_title') ?>
                </div>
                <a href="<?= site_url('api-users/create') ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i><?= lang('App.api_users_create') ?>
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($users)) : ?>
                <div class="alert alert-info">
                    <?= lang('App.api_users_empty') ?>
                </div>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th><?= lang('App.api_users_external_id') ?></th>
                                <th><?= lang('App.api_users_usage_month') ?></th>
                                <th><?= lang('App.api_users_usage_total') ?></th>
                                <th><?= lang('App.common_status') ?></th>
                                <th><?= lang('App.api_users_last_activity') ?></th>
                                <th><?= lang('App.common_actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user) : ?>
                                <tr>
                                    <td><?= esc($user['external_id']) ?></td>
                                    <td>
                                        <?= number_format($user['monthly_usage'] ?? 0) ?> tokens
                                        <?php if (($user['monthly_usage'] ?? 0) >= ($user['quota'] ?? 0)): ?>
                                            <span class="badge bg-danger ms-1"><?= lang('App.api_users_quota_exceeded') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= number_format($user['usage']['total_tokens'] ?? 0) ?> tokens<br>
                                        <small class="text-muted">
                                            <?= number_format($user['usage']['total_requests'] ?? 0) ?> <?= lang('App.api_users_requests') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                data-user-id="<?= esc($user['user_id']) ?>"
                                                <?= $user['active'] ? 'checked' : '' ?> 
                                                onchange="toggleUserStatus(this)">
                                        </div>
                                    </td>
                                    <td>
                                        <?= $user['last_activity'] ? date('Y-m-d H:i', strtotime($user['last_activity'])) : lang('App.api_users_not_set') ?>
                                    </td>
                                    <td>
                                        <a href="<?= site_url('api-users/edit/' . $user['user_id']) ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> <?= lang('App.common_edit') ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleUserStatus(checkbox) {
    const userId = checkbox.dataset.userId;
    if (confirm('<?= lang('App.api_users_toggle_status_confirm') ?>')) {
        fetch(`<?= site_url('api-users/toggle-status/') ?>${userId}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                checkbox.checked = data.active;
            } else {
                checkbox.checked = !checkbox.checked;
                alert(data.error || '<?= lang('App.error_updating_status') ?>');
            }
        })
        .catch(error => {
            checkbox.checked = !checkbox.checked;
            alert('<?= lang('App.error_updating_status_try_again') ?>');
        });
    } else {
        checkbox.checked = !checkbox.checked;
    }
}
</script>
<?= $this->endSection() ?>
