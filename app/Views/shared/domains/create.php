<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h1><?= lang('App.domains_create') ?></h1>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5><?= lang('App.domains_add') ?></h5>
        </div>
        <div class="card-body">
            <form action="<?= site_url('domains/store') ?>" method="post">
                <?= csrf_field() ?>
                
                <div class="mb-3">
                    <label for="domain" class="form-label"><?= lang('App.domains_domain') ?></label>
                    <input type="text" class="form-control" id="domain" name="domain" 
                           value="<?= old('domain') ?>" required 
                           placeholder="ejemplo.com">
                    <div class="form-text">
                        <?= lang('App.domains_input_help') ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="alert alert-info">
                        <p><strong><?= lang('App.general_note') ?>:</strong> <?= lang('App.domains_verification_note') ?></p>
                        <p><?= lang('App.domains_verification_time') ?></p>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?= site_url('domains') ?>" class="btn btn-secondary"><?= lang('App.general_cancel') ?></a>
                    <button type="submit" class="btn btn-primary"><?= lang('App.domains_save') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
