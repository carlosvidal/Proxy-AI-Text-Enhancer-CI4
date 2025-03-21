<?php

/**
 * Log an error message with context
 */
function log_error(string $source, string $message, array $context = [])
{
    $logger = service('logger');
    $logger->error("[{$source}] {$message}", $context);
}

/**
 * Log a debug message with context
 */
function log_debug(string $source, string $message, array $context = [])
{
    $logger = service('logger');
    $logger->debug("[{$source}] {$message}", $context);
}

/**
 * Log an info message with context
 */
function log_info(string $source, string $message, array $context = [])
{
    $logger = service('logger');
    $logger->info("[{$source}] {$message}", $context);
}
