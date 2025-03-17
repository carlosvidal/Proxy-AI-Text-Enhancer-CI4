<!DOCTYPE html>
<html lang="<?= service('request')->getLocale() ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : lang('App.auth_app_name'); ?></title>

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
                <?= lang('App.auth_app_name') ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (session()->get('isLoggedIn')): ?>
                        <?php if (session()->get('role') === 'superadmin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= site_url('admin/dashboard') ?>"><?= lang('App.nav_admin_panel') ?></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= site_url('admin/tenants') ?>"><?= lang('App.nav_tenants') ?></a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= site_url('usage') ?>"><?= lang('App.nav_usage') ?></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= site_url('buttons') ?>"><?= lang('App.nav_buttons') ?></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= site_url('domains') ?>"><?= lang('App.nav_domains') ?></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= site_url('api-keys') ?>"><?= lang('App.nav_api_keys') ?></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= site_url('api-users') ?>"><?= lang('App.nav_api_users') ?></a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= site_url('auth/profile') ?>"><?= lang('App.nav_my_profile') ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= site_url('auth/logout') ?>"><?= lang('App.nav_logout') ?></a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= site_url('auth/login') ?>"><?= lang('App.nav_login') ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-try-free" href="<?= site_url('auth/register') ?>"><?= lang('App.nav_register') ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Language Selector -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= strtoupper(service('request')->getLocale()) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
                            <li><a class="dropdown-item <?= service('request')->getLocale() === 'en' ? 'active' : '' ?>" href="<?= site_url('language/en') ?>">English</a></li>
                            <li><a class="dropdown-item <?= service('request')->getLocale() === 'es' ? 'active' : '' ?>" href="<?= site_url('language/es') ?>">Español</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container pt-4">
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
        
        <?php if (session()->getFlashdata('validation_error')): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach (session()->getFlashdata('validation_error') as $field => $error): ?>
                        <li><?= is_array($error) ? implode(', ', $error) : $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>