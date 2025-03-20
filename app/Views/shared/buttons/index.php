<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Buttons for <?= esc($tenant['name']) ?></h2>
    </div>
    <a href="<?= site_url('buttons/create') ?>" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Create Button
    </a>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-puzzle-piece me-1"></i>
        Button List
    </div>
    <div class="card-body">
        <?php if (empty($buttons)): ?>
            <p class="text-muted text-center">No buttons found for this tenant.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Button ID</th>
                            <th>Domain</th>
                            <th>Provider</th>
                            <th>Model</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($buttons as $button): ?>
                            <tr>
                                <td><?= esc($button['name']) ?></td>
                                <td><code><?= esc($button['button_id']) ?></code></td>
                                <td><?= esc($button['domain']) ?></td>
                                <td>
                                    <span class="badge badge-provider"><?= esc($button['provider']) ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-model"><?= esc($button['model']) ?></span>
                                </td>
                                <td>
                                    <?php if ($button['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('Y-m-d', strtotime($button['created_at'])) ?></td>
                                <td>
                                    <a href="<?= site_url('buttons/view/' . $button['button_id']) ?>" class="btn btn-sm btn-info text-white" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= site_url('buttons/edit/' . $button['button_id']) ?>" class="btn btn-sm btn-warning text-white" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= site_url('buttons/delete/' . $button['button_id']) ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this button?')">
                                        <i class="fas fa-trash"></i>
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

<?= $this->endSection() ?>