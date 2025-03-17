<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><?= lang('App.domains_title') ?></h1>
        </div>
        <div class="col-md-6 text-end">
            <?php if (count($domains) < $tenant['max_domains']): ?>
                <a href="<?= site_url('domains/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> <?= lang('App.domains_add') ?>
                </a>
            <?php else: ?>
                <button class="btn btn-secondary" disabled title="<?= sprintf(lang('App.domains_error_limit_reached')) ?>">
                    <i class="bi bi-plus-circle"></i> <?= sprintf(lang('App.domains_limit_reached'), $tenant['max_domains']) ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h5><?= lang('App.domains_registered') ?></h5>
                </div>
                <div class="col-md-6 text-end">
                    <span class="badge bg-info">
                        <?= sprintf(lang('App.domains_count'), count($domains), $tenant['max_domains']) ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($domains)): ?>
                <div class="alert alert-info">
                    <?= lang('App.domains_empty') ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><?= lang('App.domains_domain') ?></th>
                                <th><?= lang('App.domains_status') ?></th>
                                <th><?= lang('App.domains_registration_date') ?></th>
                                <th><?= lang('App.domains_actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($domains as $domain): ?>
                                <tr>
                                    <td><?= $domain['domain'] ?></td>
                                    <td>
                                        <?php if ($domain['verified']): ?>
                                            <span class="badge bg-success"><?= lang('App.domains_verified') ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning"><?= lang('App.domains_pending') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($domain['created_at'])) ?></td>
                                    <td>
                                        <?php if (!$domain['verified']): ?>
                                            <a href="<?= site_url('domains/verify/' . $domain['domain_id']) ?>" class="btn btn-sm btn-success">
                                                <i class="bi bi-check-circle"></i> <?= lang('App.domains_verify') ?>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="<?= site_url('domains/delete/' . $domain['domain_id']) ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('<?= lang('App.domains_delete_confirm') ?>')">
                                            <i class="bi bi-trash"></i> <?= lang('App.domains_delete') ?>
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
    
    <div class="card mt-4">
        <div class="card-header">
            <h5><?= lang('App.domains_info_title') ?></h5>
        </div>
        <div class="card-body">
            <p><?= lang('App.domains_info_text') ?></p>
            
            <h6><?= lang('App.domains_verification_title') ?></h6>
            <p><?= lang('App.domains_verification_text') ?></p>
            <pre>proxy-ai-verify=<?= $tenant['tenant_id'] ?></pre>
            
            <p><?= lang('App.domains_verification_button') ?></p>
            
            <h6><?= lang('App.domains_limits_title') ?></h6>
            <ul>
                <li><strong><?= lang('App.domains_limits_free') ?>:</strong> <?= lang('App.domains_limits_free_value') ?></li>
                <li><strong><?= lang('App.domains_limits_basic') ?>:</strong> <?= lang('App.domains_limits_basic_value') ?></li>
                <li><strong><?= lang('App.domains_limits_premium') ?>:</strong> <?= lang('App.domains_limits_premium_value') ?></li>
                <li><strong><?= lang('App.domains_limits_enterprise') ?>:</strong> <?= lang('App.domains_limits_enterprise_value') ?></li>
            </ul>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
