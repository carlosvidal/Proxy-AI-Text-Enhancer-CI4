<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class CorsFilter implements FilterInterface
{
    /**
     * Cache for allowed domains to reduce database queries
     * @var array
     */
    private static $domainCache = null;
    
    /**
     * Cache lifetime in seconds (5 minutes)
     * @var int
     */
    private $cacheTtl = 300;
    
    /**
     * Get all allowed domains from configuration and database
     * 
     * @return array Array of allowed domains
     */
    private function getAllowedDomains()
    {
        // Try to get from cache first
        if (self::$domainCache !== null && (time() - self::$domainCache['time'] < $this->cacheTtl)) {
            log_debug('CORS', 'Using cached domains list', [
                'count' => count(self::$domainCache['domains']),
                'cache_age' => time() - self::$domainCache['time'] . ' seconds'
            ]);
            return self::$domainCache['domains'];
        }
        
        $allowedDomains = [];
        
        try {
            // Get domains from database - only from active buttons
            $db = \Config\Database::connect();
            
            // Get all domains from buttons table with status = 'active'
            $buttonsQuery = $db->query("
                SELECT DISTINCT domain FROM buttons 
                WHERE status = 'active'
            ");
            
            if ($buttonsQuery) {
                $buttonRows = $buttonsQuery->getResultArray();
                foreach ($buttonRows as $row) {
                    if (!empty($row['domain'])) {
                        // Extract just the domain part if it's a URL
                        $parsed = parse_url($row['domain']);
                        if (isset($parsed['host'])) {
                            $allowedDomains[] = $parsed['host'];
                        } else {
                            $allowedDomains[] = $row['domain'];
                        }
                    }
                }
            }

            // Log the domains found
            log_debug('CORS', 'Found domains from active buttons', [
                'count' => count($allowedDomains),
                'domains' => $allowedDomains
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error getting allowed domains from database: ' . $e->getMessage());
        }
        
        // Remove duplicates and empty values
        $allowedDomains = array_filter(array_unique($allowedDomains));
        
        // Cache the result
        self::$domainCache = [
            'domains' => $allowedDomains,
            'time' => time()
        ];
        
        log_debug('CORS', 'Loaded domains from database', [
            'count' => count($allowedDomains),
            'domains' => implode(', ', $allowedDomains)
        ]);
        
        return $allowedDomains;
    }
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('logger');
        
        // Get origin from request
        $origin = $request->getHeaderLine('Origin');
        
        // Leer orígenes permitidos desde .env y procesar la lista
        $allowedOriginsEnv = env('ALLOWED_ORIGINS', '*');
        $allowedOrigins = array_map('trim', explode(',', $allowedOriginsEnv));
        
        // Log the requesting origin
        log_message('debug', 'Request origin: ' . ($origin ?: 'none'));
        
        // If we have a requesting origin and it's not empty
        if (!empty($origin)) {
            // Extract domain from origin
            $originDomain = parse_url($origin, PHP_URL_HOST);
            
            // Check if this is a development environment
            $isDevelopment = (ENVIRONMENT === 'development');
            
            // Check if the wildcard is allowed in this environment (only in development)
            $allowWildcard = $isDevelopment && env('ALLOWED_ORIGINS', '*') === '*';
            
            // Get allowed domains from configuration + database
            $allowedDomains = $this->getAllowedDomains();

            // Fusionar allowedOrigins (.env) y allowedDomains (DB/config)
            $allAllowed = array_merge($allowedOrigins, $allowedDomains);

            log_debug('CORS', 'Checking domain access', [
                'origin' => $origin,
                'allowed_env' => $allowedOrigins,
                'allowed_domains' => $allowedDomains,
                'all_allowed' => $allAllowed,
                'allow_wildcard' => $allowWildcard ? 'true' : 'false'
            ]);

            // Allow access if wildcard is enabled or if the origin is in our allowed list
            if ($allowWildcard) {
                header('Access-Control-Allow-Origin: *');
                log_debug('CORS header set: Access-Control-Allow-Origin: *', '');
            } else {
                $domainAllowed = false;
                // Comparar el Origin completo (protocolo, host y puerto)
                foreach ($allAllowed as $allowed) {
                    if ($origin === $allowed) {
                        $domainAllowed = true;
                        break;
                    }
                }
                // Compatibilidad: si no hay coincidencia exacta, probar lógica original (host y wildcard)
                if (!$domainAllowed) {
                    $originHost = parse_url($origin, PHP_URL_HOST);
                    foreach ($allAllowed as $allowedDomain) {
                        // Coincidencia exacta solo host
                        if ($allowedDomain === $originHost) {
                            $domainAllowed = true;
                            break;
                        }
                        // Coincidencia wildcard subdominio
                        if (strpos($allowedDomain, '*.') === 0) {
                            $mainDomain = substr($allowedDomain, 2); // Remove *. prefix
                            if (substr($originHost, -strlen($mainDomain)) === $mainDomain && 
                                substr_count($originHost, '.') >= substr_count($mainDomain, '.') + 1) {
                                $domainAllowed = true;
                                break;
                            }
                        }
                    }
                }
                if ($domainAllowed) {
                    header("Access-Control-Allow-Origin: {$origin}");
                    header('Access-Control-Allow-Credentials: true');
                    log_debug('CORS header set: Access-Control-Allow-Origin: ' . $origin, '');
                } else {
                    log_debug('Origin not allowed: ' . $origin, '');
                    return;
                }
            }

            // Get current route
            $uri = $request->getUri();
            $path = $uri->getPath();

            // Set basic CORS headers
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

            // Route-specific CORS settings
            if (strpos($path, 'api/llm-proxy') === 0) {
                if ($request->getMethod() === 'options') {
                    // LLM proxy routes - higher cache for preflight
                    header('Access-Control-Max-Age: 86400'); // 24 hours
                    header('Cache-Control: public, max-age=86400');
                } else {
                    // No caching for actual proxy requests
                    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                    header('Pragma: no-cache');
                }
                
                if (strpos($path, '/secure') !== false) {
                    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
                }
            } else if (strpos($path, 'api/quota') === 0) {
                if ($request->getMethod() === 'options') {
                    // Quota routes - shorter cache for preflight
                    header('Access-Control-Max-Age: 3600'); // 1 hour
                    header('Cache-Control: public, max-age=3600');
                } else {
                    // Short cache for quota checks
                    header('Cache-Control: private, must-revalidate, max-age=60'); // 1 minute
                }
                
                if (strpos($path, '/secure') !== false) {
                    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
                }
            } else {
                if ($request->getMethod() === 'options') {
                    // Default routes - moderate cache for preflight
                    header('Access-Control-Max-Age: 7200'); // 2 hours
                    header('Cache-Control: public, max-age=7200');
                } else {
                    // Default cache policy
                    header('Cache-Control: private, must-revalidate, max-age=300'); // 5 minutes
                }
            }

            // Add Vary header for proper caching
            header('Vary: Origin, Access-Control-Request-Method, Access-Control-Request-Headers');

            // Handle preflight OPTIONS requests
            if ($request->getMethod() === 'options') {
                log_message('debug', 'Handling OPTIONS preflight request for path: ' . $path);
                header('Content-Length: 0');
                header('Content-Type: text/plain');
                exit(0);
            }

            return $request;
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
        return $response;
    }
}
