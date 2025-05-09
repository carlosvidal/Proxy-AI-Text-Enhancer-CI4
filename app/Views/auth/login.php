<?php
$currentUrl = current_url();
$hasIndexPhp = strpos($currentUrl, 'index.php') !== false;
?>
<!DOCTYPE html>
<html lang="<?= service('request')->getLocale() ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : lang('App.auth_login_title') ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            min-height: 100vh;
        }

        .login-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 15px;
        }

        .login-card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: white;
            padding: 30px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .login-header .logo {
            font-size: 2.5rem;
            color: #3b82f6;
            margin-bottom: 10px;
        }

        .btn-primary {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }

        .btn-primary:hover {
            background-color: #2563eb;
            border-color: #2563eb;
        }
    </style>
</head>

<body>
    <div class="container login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-robot"></i>
                </div>
                <h3><?= lang('App.auth_app_name') ?></h3>
                <p class="text-muted"><?= lang('App.auth_sign_in') ?></p>
            </div>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger">
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <form action="<?= site_url('auth/login') ?>" method="post">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="username" class="form-label"><?= lang('App.auth_username_email') ?></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" value="<?= old('username') ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label"><?= lang('App.auth_password') ?></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary"><?= lang('App.auth_sign_in_button') ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>