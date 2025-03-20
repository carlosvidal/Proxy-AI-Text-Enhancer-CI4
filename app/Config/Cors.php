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
        // Los dominios se obtienen dinámicamente de la base de datos
        // en la clase CorsFilter usando el método getAllowedDomains()
        // basado en los dominios de los botones activos
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
