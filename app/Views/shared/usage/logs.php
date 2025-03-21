<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h2><?= esc($title) ?></h2>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Botón</th>
                        <th>Usuario</th>
                        <th>Tokens</th>
                        <th>Costo</th>
                        <th>Prompt</th>
                        <th>Respuesta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= date('Y-m-d H:i:s', strtotime($log->created_at)) ?></td>
                        <td><?= esc($log->button_name ?? 'API Request') ?></td>
                        <td><?= esc($log->user_name ?? $log->external_id) ?></td>
                        <td><?= number_format($log->tokens) ?></td>
                        <td>$<?= number_format($log->cost, 4) ?></td>
                        <td>
                            <?php if ($log->messages): ?>
                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#promptModal<?= $log->id ?>">
                                Ver Prompt
                            </button>
                            <!-- Modal -->
                            <div class="modal fade" id="promptModal<?= $log->id ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Prompt</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <pre class="bg-light p-3"><code><?= esc(json_encode(json_decode($log->messages), JSON_PRETTY_PRINT)) ?></code></pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log->response): ?>
                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#responseModal<?= $log->id ?>">
                                Ver Respuesta
                            </button>
                            <!-- Modal -->
                            <div class="modal fade" id="responseModal<?= $log->id ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Respuesta</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <pre class="bg-light p-3"><code><?= esc(json_encode(json_decode($log->response), JSON_PRETTY_PRINT)) ?></code></pre>
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
<?= $this->endSection() ?>