<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'AI Text Enhancer Pro'; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            min-height: 100vh;
            padding-top: 60px;
        }
        .navbar-brand i {
            margin-right: 0.5rem;
        }
        .btn-try-free {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }
        .btn-try-free:hover {
            background-color: #2563eb;
            border-color: #2563eb;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-robot"></i>
                AI Text Enhancer Pro
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (session()->get('isLoggedIn')): ?>
                        <?php if (session()->get('role') === 'superadmin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= site_url('admin/dashboard') ?>">Admin Panel</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= site_url('admin/tenants') ?>">Tenants</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= site_url('usage') ?>">Usage</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= site_url('buttons') ?>">Buttons</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= site_url('api-users') ?>">API Users</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= site_url('auth/profile') ?>">My Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= site_url('auth/logout') ?>">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= site_url('auth/login') ?>">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-try-free" href="<?= site_url('auth/register') ?>">Try Free</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container mt-5 pt-4">
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