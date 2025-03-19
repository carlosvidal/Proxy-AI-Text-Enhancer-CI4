<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Cors extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Cross-Origin Resource Sharing (CORS) Settings
     * --------------------------------------------------------------------------
     *
     * Here you can configure your settings for cross-origin resource sharing
     * or CORS. This determines what cross-origin operations may execute
     * in web browsers.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
     */

    /**
     * --------------------------------------------------------------------------
     * Allowed HTTP Methods
     * --------------------------------------------------------------------------
     *
     * @var array
     */
    public array $allowedMethods = ['*'];

    /**
     * --------------------------------------------------------------------------
     * Allowed HTTP Headers
     * --------------------------------------------------------------------------
     *
     * @var array
     */
    public array $allowedHeaders = ['*'];

    /**
     * --------------------------------------------------------------------------
     * Allowed HTTP Origins
     * --------------------------------------------------------------------------
     *
     * @var array
     */
    public array $allowedOrigins = [
        // Lista de dominios permitidos para desarrollo local
        'http://llmproxy2.test:8080',
        'http://127.0.0.1:54323',
        'http://localhost:8080',
        'http://localhost:8081',
        'http://localhost:8082',
        'http://localhost:8083',
        'http://localhost:8084'
        // Los dominios de producción se obtienen dinámicamente de la base de datos
        // en la clase CorsFilter usando el método getAllowedDomains()
    ];

    /**
     * --------------------------------------------------------------------------
     * Exposed HTTP Headers
     * --------------------------------------------------------------------------
     *
     * @var array
     */
    public array $exposedHeaders = [];

    /**
     * --------------------------------------------------------------------------
     * Max Age
     * --------------------------------------------------------------------------
     *
     * @var int
     */
    public int $maxAge = 0;

    /**
     * --------------------------------------------------------------------------
     * Should we allow Credentials
     * --------------------------------------------------------------------------
     *
     * @var bool
     */
    public bool $allowCredentials = true;
}
