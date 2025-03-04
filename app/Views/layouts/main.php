<?= view('layouts/header', $this->data ?? []) ?>

<?= $this->renderSection('content') ?>

<?= view('layouts/footer') ?>