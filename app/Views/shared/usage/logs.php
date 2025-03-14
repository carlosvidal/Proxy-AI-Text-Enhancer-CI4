<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4">
    <h1 class="mt-4"><?= $title ?></h1>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Usage Logs
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Button</th>
                        <th>Tokens</th>
                        <th>Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= date('Y-m-d H:i:s', strtotime($log->created_at)) ?></td>
                        <td><?= esc($log->button_name ?? 'API Request') ?></td>
                        <td><?= number_format($log->tokens) ?></td>
                        <td>$<?= number_format($log->cost, 4) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>