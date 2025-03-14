<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Session\Handlers\BaseHandler;
use CodeIgniter\Session\Handlers\FileHandler;

class Session extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Session Driver
     * --------------------------------------------------------------------------
     */
    public string $driver = FileHandler::class;

    /**
     * --------------------------------------------------------------------------
     * Session Cookie Name
     * --------------------------------------------------------------------------
     */
    public string $cookieName = 'llm_proxy_session';

    /**
     * --------------------------------------------------------------------------
     * Session Expiration
     * --------------------------------------------------------------------------
     */
    public int $expiration = 3600;

    /**
     * --------------------------------------------------------------------------
     * Session Save Path
     * --------------------------------------------------------------------------
     */
    public string $savePath = WRITEPATH . 'sessions';

    /**
     * --------------------------------------------------------------------------
     * Session Match IP
     * --------------------------------------------------------------------------
     */
    public bool $matchIP = false;

    /**
     * --------------------------------------------------------------------------
     * Session Time to Update
     * --------------------------------------------------------------------------
     */
    public int $timeToUpdate = 300;

    /**
     * --------------------------------------------------------------------------
     * Session Regenerate Destroy
     * --------------------------------------------------------------------------
     */
    public bool $regenerateDestroy = true;

    /**
     * --------------------------------------------------------------------------
     * Session Database Group
     * --------------------------------------------------------------------------
     */
    public ?string $DBGroup = null;

    /**
     * --------------------------------------------------------------------------
     * Cookie Settings
     * --------------------------------------------------------------------------
     */
    public bool $cookieSecure = false;
    public bool $cookieHTTPOnly = true;
    public string $cookieDomain = '';  // Let Apache handle the domain
    public string $cookiePath = '/';
    public string $cookieSameSite = 'Lax';
}
