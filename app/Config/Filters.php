<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseConfig
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, class-string|list<class-string>> [filter_name => classname]
     *                                                     or [filter_name => [classname1, classname2, ...]]
     */
    public array $aliases = [
        'csrf'     => \CodeIgniter\Filters\CSRF::class,
        'toolbar'  => \CodeIgniter\Filters\DebugToolbar::class,
        'honeypot' => \CodeIgniter\Filters\Honeypot::class,
        'role'     => \App\Filters\RoleFilter::class,
        'cors' => \App\Filters\CorsFilter::class, // CORS filter
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'auth' => \App\Filters\AuthFilter::class,
        'jwt' => \App\Filters\JwtFilter::class, // Added JWT filter
        'compress' => \App\Filters\CompressionFilter::class, // Added compression filter
        'language' => \App\Filters\LanguageFilter::class, // Added language filter
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array<string, array<string, array<string, string>>>|array<string, list<string>>
     */
    public array $globals = [
        'before' => [
            // 'csrf',
            'honeypot',
            'invalidchars',
            'language', // Apply language filter to all requests
        ],
        'after' => [
            'toolbar',
            // 'honeypot',
            // 'secureheaders',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'post' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don't expect could bypass the filter.
     *
     * @var array<string, list<string>>
     */
    public array $methods = [];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [
        'auth' => [
            'before' => [
                'usage*',
                'tenants*',
                'auth/profile',
                'auth/updateProfile'
            ]
        ],
        'jwt' => [
            'before' => [
                'api/llm-proxy/secure',  // Example of a secured endpoint
                'api/quota/secure',      // Example of a secured quota endpoint
            ]
        ],
        'cors' => [
            'before' => [
                'api/*',  // Apply CORS to all API routes
            ]
        ],
        'compress' => [
            'after' => [
                'api/llm-proxy/*',  // All LLM proxy endpoints
                'api/quota/*',      // All quota endpoints
                'api/*/stats',      // Any stats endpoints
                'api/*/usage',      // Any usage endpoints
            ]
        ],
    ];
}
