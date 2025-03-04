<!DOCTYPE html>
<html lang="en">
<?php // Remove the defined('BASEPATH') line as it's not needed in CI4 
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'LLM Proxy Usage Dashboard'; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        /* CSS styles remain the same */
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?= site_url('usage') ?>">
                <i class="fas fa-robot me-2"></i>
                LLM Proxy Dashboard
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= uri_string() == 'usage' || uri_string() == 'usage/index' ? 'active' : '' ?>" href="<?= site_url('usage') ?>">
                            <i class="fas fa-chart-line me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos(uri_string(), 'usage/logs') === 0 ? 'active' : '' ?>" href="<?= site_url('usage/logs') ?>">
                            <i class="fas fa-list-alt me-1"></i> Usage Logs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= uri_string() == 'usage/quotas' ? 'active' : '' ?>" href="<?= site_url('usage/quotas') ?>">
                            <i class="fas fa-user-shield me-1"></i> User Quotas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= uri_string() == 'usage/providers' ? 'active' : '' ?>" href="<?= site_url('usage/providers') ?>">
                            <i class="fas fa-server me-1"></i> Providers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= uri_string() == 'usage/cache' ? 'active' : '' ?>" href="<?= site_url('usage/cache') ?>">
                            <i class="fas fa-memory me-1"></i> Cache
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos(uri_string(), 'tenants') === 0 ? 'active' : '' ?>" href="<?= site_url('tenants') ?>">
                            <i class="fas fa-building me-1"></i> Tenants
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <h1 class="mb-4"><?= isset($title) ? $title : 'LLM Proxy Usage Dashboard'; ?></h1>

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