<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-circle me-1"></i>
                <?= lang('App.auth_profile_title') ?>
            </div>
            <div class="card-body">
                <form action="<?= site_url('auth/profile') ?>" method="post">
                    <?= csrf_field() ?>

                    <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-success">
                            <?= session()->getFlashdata('success') ?>
                        </div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger">
                            <?= session()->getFlashdata('error') ?>
                        </div>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-md-3 text-center">
                            <div class="mb-3">
                                <div style="width: 120px; height: 120px; margin: 0 auto; background-color: #e9ecef; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-user" style="font-size: 60px; color: #adb5bd;"></i>
                                </div>
                            </div>
                            <div class="text-muted">
                                <strong><?= esc($user['role']) ?></strong>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="mb-3">
                                <label for="username" class="form-label"><?= lang('App.auth_profile_username') ?></label>
                                <input type="text" class="form-control" id="username" value="<?= esc($user['username']) ?>" readonly>
                                <div class="form-text"><?= lang('App.auth_profile_username') ?> <?= lang('App.cannot_be_changed') ?></div>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label"><?= lang('App.auth_profile_name') ?></label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= esc($user['name']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label"><?= lang('App.auth_profile_email') ?></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= esc($user['email']) ?>" required>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h5 class="mt-4 mb-3"><?= lang('App.change_password') ?></h5>
                    <p class="text-muted mb-4"><?= lang('App.leave_password_empty') ?></p>

                    <div class="mb-3">
                        <label for="password" class="form-label"><?= lang('App.auth_profile_new_password') ?></label>
                        <input type="password" class="form-control" id="password" name="password">
                        <div class="form-text"><?= lang('App.password_requirements') ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirm" class="form-label"><?= lang('App.auth_profile_confirm_password') ?></label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm">
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="<?= site_url('usage') ?>" class="btn btn-secondary"><?= lang('App.cancel') ?></a>
                        <button type="submit" class="btn btn-primary"><?= lang('App.save') ?></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <i class="fas fa-shield-alt me-1"></i>
                <?= lang('App.security_information') ?>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><?= lang('App.last_login') ?></h6>
                        <p class="text-muted">
                            <?= isset($user['last_login']) ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : lang('App.not_available') ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6><?= lang('App.account_created') ?></h6>
                        <p class="text-muted">
                            <?= isset($user['created_at']) ? date('Y-m-d H:i:s', strtotime($user['created_at'])) : lang('App.not_available') ?>
                        </p>
                    </div>
                </div>

                <div class="alert alert-info mt-3" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <?= lang('App.security_reminder') ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>