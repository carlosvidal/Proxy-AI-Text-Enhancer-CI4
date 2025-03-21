<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2><?= esc($title) ?></h2>
            <button type="button" class="btn btn-primary" id="toggleDetails">Mostrar/Ocultar Detalles</button>
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
                        <th class="details-column d-none">System Prompt</th>
                        <th class="details-column d-none">Prompt</th>
                        <th class="details-column d-none">Respuesta</th>
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
                        <td class="details-column d-none">
                            <?php if ($log->system_prompt): ?>
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#systemPromptModal<?= $log->id ?>">
                                Ver System Prompt
                                <?php if ($log->system_prompt_source): ?>
                                <span class="badge bg-info"><?= esc($log->system_prompt_source) ?></span>
                                <?php endif; ?>
                            </button>
                            <div class="modal fade" id="systemPromptModal<?= $log->id ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">System Prompt 
                                                <?php if ($log->system_prompt_source): ?>
                                                <span class="badge bg-info"><?= esc($log->system_prompt_source) ?></span>
                                                <?php endif; ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <pre class="bg-light p-3"><code><?= esc($log->system_prompt) ?></code></pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="details-column d-none">
                            <?php if ($log->messages): ?>
                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#promptModal<?= $log->id ?>">
                                Ver Prompt
                            </button>
                            <div class="modal fade" id="promptModal<?= $log->id ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Prompt</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
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
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="details-column d-none">
                            <?php if ($log->response): ?>
                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#responseModal<?= $log->id ?>">
                                Ver Respuesta
                            </button>
                            <div class="modal fade" id="responseModal<?= $log->id ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Respuesta</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php 
                                            $response = json_decode($log->response);
                                            if (isset($response->content)): 
                                            ?>
                                            <pre class="bg-light p-3"><code><?= esc($response->content) ?></code></pre>
                                            <?php else: ?>
                                            <pre class="bg-light p-3"><code><?= esc(json_encode($response, JSON_PRETTY_PRINT)) ?></code></pre>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleButton = document.getElementById('toggleDetails');
    const detailsColumns = document.querySelectorAll('.details-column');
    
    toggleButton.addEventListener('click', function() {
        detailsColumns.forEach(column => {
            column.classList.toggle('d-none');
        });
    });
});
</script>

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