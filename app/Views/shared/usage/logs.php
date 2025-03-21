<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2><?= esc($title) ?></h2>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Botón</th>
                        <th>Usuario</th>
                        <th>Tokens</th>
                        <th>Costo</th>
                        <th>Detalles</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= date('Y-m-d H:i:s', strtotime($log->created_at)) ?></td>
                        <td><?= esc($log->button_name) ?></td>
                        <td><?= esc($log->user_identifier ?? $log->external_id) ?></td>
                        <td><?= number_format($log->tokens) ?></td>
                        <td>$<?= number_format($log->cost, 4) ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#promptModal<?= $log->id ?>">
                                <i class="bi bi-eye"></i>
                            </button>
                            <div class="modal fade" id="promptModal<?= $log->id ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Detalles del Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php if ($log->system_prompt): ?>
                                            <div class="mb-4">
                                                <h6>System Prompt <?php if ($log->system_prompt_source): ?><span class="badge bg-info"><?= esc($log->system_prompt_source) ?></span><?php endif; ?></h6>
                                                <pre class="bg-light p-3"><code><?= esc($log->system_prompt) ?></code></pre>
                                            </div>
                                            <?php endif; ?>

                                            <?php if ($log->messages): ?>
                                            <div class="mb-4">
                                                <h6>Messages</h6>
                                                <?php 
                                                $messages = json_decode($log->messages);
                                                if ($messages): 
                                                ?>
                                                <div class="messages-container">
                                                    <?php foreach ($messages as $msg): ?>
                                                    <div class="message mb-3">
                                                        <div class="message-role fw-bold"><?= esc($msg->role) ?></div>
                                                        <pre class="bg-light p-3 mt-2"><code><?= esc($msg->content) ?></code></pre>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>

                                            <?php if ($log->response): ?>
                                            <div class="mb-4">
                                                <h6>Response</h6>
                                                <pre class="bg-light p-3"><code><?= esc($log->response) ?></code></pre>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.message-role {
    color: #666;
    text-transform: uppercase;
    font-size: 0.9em;
}
pre {
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>
<?= $this->endSection() ?>