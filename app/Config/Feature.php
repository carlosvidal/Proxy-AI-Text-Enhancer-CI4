<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Enable/disable backward compatibility breaking features.
 *
 * NOTE: The flags below are intentionally set to preserve the runtime behavior
 * this app had on CodeIgniter 4.4. Flip them to the framework defaults
 * (autoRoutesImproved = true, oldFilterOrder = false) deliberately, with testing,
 * if/when you want the newer behavior.
 */
class Feature extends BaseConfig
{
    /**
     * Use improved new auto routing instead of the legacy version.
     * Kept false to preserve the 4.4 routing behavior this app relied on.
     */
    public bool $autoRoutesImproved = false;

    /**
     * Use filter execution order in 4.4 or before.
     * Kept true so the existing auth/cors/jwt filter ordering is unchanged.
     */
    public bool $oldFilterOrder = true;

    /**
     * The behavior of `limit(0)` in Query Builder.
     *
     * If true, `limit(0)` returns all records. (the behavior of 4.4.x or before.)
     * If false, `limit(0)` returns no records. (the behavior of 3.x.)
     */
    public bool $limitZeroAsAll = true;

    /**
     * Use strict location negotiation.
     *
     * By default, the locale is selected based on a loose comparison of the
     * language code (ISO 639-1). Enabling strict comparison will also consider
     * the region code (ISO 3166-1 alpha-2).
     */
    public bool $strictLocaleNegotiation = false;
}
