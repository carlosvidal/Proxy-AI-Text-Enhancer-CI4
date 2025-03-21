<?php

/*
 |--------------------------------------------------------------------------
 | ERROR DISPLAY
 |--------------------------------------------------------------------------
 | In production, we want to not show any errors to the public, so disable
 | error display and send all errors to the system log.
 */
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);

/*
 |--------------------------------------------------------------------------
 | DEBUG MODE
 |--------------------------------------------------------------------------
 | Debug mode is an experimental flag that can allow changes throughout
 | the system. This will control whether Kint is loaded, and a few other items.
 | It can always be used within your own application too.
 */
defined('CI_DEBUG') || define('CI_DEBUG', false);

// Disable debug toolbar
defined('SHOW_DEBUG_TOOLBAR') || define('SHOW_DEBUG_TOOLBAR', false);
