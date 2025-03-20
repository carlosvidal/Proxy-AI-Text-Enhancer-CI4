<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4">
    <h1 class="mt-4"><?= lang('App.api_users_edit') ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= site_url('/dashboard') ?>"><?= lang('App.nav_dashboard') ?></a></li>
        <li class="breadcrumb-item"><a href="<?= site_url('/api-users') ?>"><?= lang('App.nav_api_users') ?></a></li>
        <li class="breadcrumb-item active"><?= lang('App.api_users_edit') ?></li>
    </ol>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user-edit me-1"></i>
            <?= lang('App.api_users_edit') ?>
        </div>
        <div class="card-body">
            <form action="<?= site_url('api-users/update/' . $api_user['id']) ?>" method="post">
                <div class="mb-3">
                    <label for="external_id" class="form-label"><?= lang('App.api_users_external_id') ?></label>
                    <input type="text" class="form-control" id="external_id" name="external_id" value="<?= esc($api_user['external_id']) ?>" required>
                    <div class="form-text"><?= lang('App.api_users_external_id_help') ?></div>
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label"><?= lang('App.api_users_name') ?></label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= esc($api_user['name']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label"><?= lang('App.api_users_email') ?></label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= esc($api_user['email']) ?>">
                    <div class="form-text"><?= lang('App.api_users_email_help') ?></div>
                </div>

                <div class="mb-3">
                    <label for="quota" class="form-label"><?= lang('App.api_users_quota') ?></label>
                    <input type="number" class="form-control" id="quota" name="quota" value="<?= esc($api_user['quota']) ?>" required min="0">
                    <div class="form-text"><?= lang('App.api_users_quota_help') ?></div>
                </div>

                <div class="mb-3">
                    <label for="daily_quota" class="form-label"><?= lang('App.api_users_daily_quota') ?></label>
                    <input type="number" class="form-control" id="daily_quota" name="daily_quota" value="<?= esc($api_user['daily_quota']) ?>" required min="0">
                    <div class="form-text"><?= lang('App.api_users_daily_quota_help') ?></div>
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="active" name="active" value="1" <?= $api_user['active'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="active"><?= lang('App.api_users_active') ?></label>
                    </div>
                    <div class="form-text"><?= lang('App.api_users_active_help') ?></div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= site_url('api-users') ?>" class="btn btn-secondary">
                        <?= lang('App.common_cancel') ?>
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <?= lang('App.common_save') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
