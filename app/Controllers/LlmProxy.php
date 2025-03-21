<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\LlmProxyModel;
use App\Libraries\LlmProviders\OpenAiProvider;
use App\Libraries\LlmProviders\AnthropicProvider;
use App\Libraries\LlmProviders\MistralProvider;
use App\Libraries\LlmProviders\DeepseekProvider;
use App\Libraries\LlmProviders\GoogleProvider;
use App\Libraries\LlmProviders\AzureProvider;

/**
 * LlmProxy Controller
 * 
 * Controlador que maneja las peticiones al proxy de LLM en CodeIgniter 4
 * 
 * @package     AI Text Enhancer
 */
class LlmProxy extends Controller
{
    /**
     * Configuración del proxy
     */
    private $api_keys = [];
    private $endpoints = [];
    private $use_simulated_responses = false;
    private $allowed_origins = '*';

    protected $llm_proxy_model;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Load necessary helpers
        helper(['url', 'form', 'logger', 'jwt', 'api_key', 'image_proxy']);

        // Initialize proxy model
        $this->llm_proxy_model = new LlmProxyModel();

        // Initialize proxy configuration
        $this->_initialize_config();

        // Log initialization with detailed context
        log_info('PROXY', 'Proxy initialized', [
            'ip' => service('request')->getIPAddress(),
            'method' => service('request')->getMethod(),
            'user_agent' => service('request')->getUserAgent()->getAgentString(),
            'request_id' => uniqid('req_'),
            'timestamp' => date('Y-m-d H:i:s'),
            'environment' => ENVIRONMENT,
            'providers_configured' => array_filter($this->api_keys, fn($key) => !empty($key))
        ]);
    }

    /**
     * Main endpoint for proxy requests
     */
    public function index()
    {
        $request_id = uniqid('proxy_');
        log_info('PROXY', 'Request received at main endpoint', [
            'request_id' => $request_id,
            'ip' => service('request')->getIPAddress(),
            'method' => service('request')->getMethod()
        ]);

        // Verify this is a POST request
        if (service('request')->getMethod() !== 'post') {
            log_error('PROXY', 'Method not allowed', [
                'request_id' => $request_id,
                'method' => service('request')->getMethod()
            ]);

            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(405)
                ->setJSON(['error' => ['message' => 'Method not allowed']]);
        }

        // Get JWT data if available
        $jwtData = null;
        $token = get_jwt_from_header();
        if ($token) {
            $tokenData = validate_jwt($token);
            if ($tokenData && isset($tokenData->data)) {
                $jwtData = $tokenData->data;
                log_info('PROXY', 'JWT authenticated user', [
                    'request_id' => $request_id,
                    'username' => $jwtData->username ?? 'unknown',
                    'id' => $jwtData->id ?? 'unknown',
                    'tenant_id' => $jwtData->tenant_id ?? 'unknown'
                ]);
            } else {
                log_warning('PROXY', 'Invalid JWT token provided', [
                    'request_id' => $request_id,
                    'token_exists' => !empty($token)
                ]);
            }
        }

        try {
            // Get request data
            $json = $this->request->getJSON();
            if (!$json) {
                throw new \Exception('Invalid request format');
            }

            // Extract request parameters
            $provider = $json->provider ?? 'openai';
            $model = $json->model ?? null;
            $messages = $json->messages ?? [];
            $options = $json->options ?? [];
            $stream = $json->stream ?? false;

            // Validate required parameters
            if (!$model || empty($messages)) {
                throw new \Exception('Missing required parameters');
            }

            // Get domain from headers
            $domain = $this->_extract_domain_from_headers();

            // Ensure user exists
            $tenant_id = $this->_ensure_user_exists($domain);

            // Get button configuration if provided
            $button = null;
            $button_id = $json->button_id ?? null;
            if ($button_id) {
                $db = db_connect();
                $button = $db->table('buttons')
                    ->where('button_id', $button_id)
                    ->where('tenant_id', $tenant_id)
                    ->get()
                    ->getRowArray();

                if ($button) {
                    $provider = $button['provider'];
                    $model = $button['model'];
                }
            }

            // Procesa la solicitud al LLM
            $response = $this->_process_llm_request($provider, $model, $messages, $options->temperature ?? 0.7, $stream, $tenant_id, $domain);

            return $response;

        } catch (\Exception $e) {
            // Log error
            log_message('error', 'Error processing LLM request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return error response
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Procesa la solicitud al LLM
     */
    private function _process_llm_request($provider, $model, $messages, $temperature, $stream, $tenant_id, $external_id, $has_image = false)
    {
        try {
            // Get LLM provider instance
            $llm = $this->_get_llm_provider($provider);

            // Set options
            $options = [
                'temperature' => $temperature,
                'stream' => $stream
            ];

            // Process request
            if ($stream) {
                log_info('PROXY', 'Processing streaming request', [
                    'provider' => $provider,
                    'model' => $model
                ]);

                // Set headers for streaming
                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache');
                header('Connection: keep-alive');
                header('X-Accel-Buffering: no');

                // Get streaming response
                $response = $llm->process_stream_request($model, $messages, $options);
                
                // Process stream response
                if (is_callable($response)) {
                    $response();
                } else {
                    log_error('PROXY', 'Invalid stream response', [
                        'provider' => $provider,
                        'type' => gettype($response)
                    ]);
                    throw new \Exception('Invalid stream response from provider');
                }

                // Log usage after streaming completes
                $usage = $llm->get_token_usage($messages);
                $this->_log_usage($tenant_id, $external_id, $provider, $model, $usage['tokens_in'], $usage['tokens_out'], $has_image);

                // Return empty response since we've already sent the stream
                return '';
            } else {
                $response = $llm->process_request($model, $messages, $options);

                // Log usage
                $this->_log_usage($tenant_id, $external_id, $provider, $model, $response['tokens_in'], $response['tokens_out'], $has_image);

                // Return successful response
                return $this->response->setJSON([
                    'success' => true,
                    'response' => $response['response'],
                    'tokens_in' => $response['tokens_in'],
                    'tokens_out' => $response['tokens_out']
                ]);
            }
        } catch (\Exception $e) {
            log_error('PROXY', 'Error processing LLM request', [
                'error' => $e->getMessage(),
                'provider' => $provider,
                'model' => $model
            ]);

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
        }
    }

    /**
     * Process LLM request
     */
    public function process()
    {
        return $this->index();
    }

    /**
     * Handle OPTIONS request for CORS
     */
    public function options()
    {
        return $this->response
            ->setHeader('Access-Control-Allow-Origin', $this->allowed_origins)
            ->setHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setStatusCode(200);
    }

    /**
     * Extract domain from request headers
     */
    private function _extract_domain_from_headers()
    {
        $origin = service('request')->getHeaderLine('Origin');
        $referer = service('request')->getHeaderLine('Referer');

        if (!empty($origin)) {
            $domain = parse_url($origin, PHP_URL_HOST);
        } elseif (!empty($referer)) {
            $domain = parse_url($referer, PHP_URL_HOST);
        } else {
            $domain = service('request')->getIPAddress();
        }

        return $domain;
    }

    /**
     * Ensures a user exists for a tenant, creating them if needed
     */
    private function _ensure_user_exists($domain)
    {
        $db = db_connect();

        // Try to find existing tenant by domain
        $tenant = $db->table('tenants')
            ->where('name', $domain)
            ->get()
            ->getRowArray();

        // If tenant doesn't exist, create one
        if (!$tenant) {
            $tenant_id = 'ten-' . bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(4));
            $db->table('tenants')->insert([
                'tenant_id' => $tenant_id,
                'name' => $domain,
                'active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            log_info('TENANT', 'Created new tenant', [
                'tenant_id' => $tenant_id,
                'domain' => $domain
            ]);

            return $tenant_id;
        }

        return $tenant['tenant_id'];
    }

    /**
     * Log usage for billing purposes
     */
    private function _log_usage($tenant_id, $external_id, $provider, $model, $tokens_in, $tokens_out, $has_image = false)
    {
        $db = db_connect();
        
        try {
            // Log usage details
            $db->table('usage_logs')->insert([
                'tenant_id' => $tenant_id,
                'user_id' => $external_id, // Using external_id as user_id
                'external_id' => $external_id,
                'provider' => $provider,
                'model' => $model,
                'tokens' => $tokens_in + $tokens_out,
                'has_image' => $has_image ? 1 : 0,
                'status' => 'success',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            log_debug('USAGE', 'Usage logged successfully', [
                'tenant_id' => $tenant_id,
                'external_id' => $external_id,
                'provider' => $provider,
                'model' => $model,
                'tokens' => $tokens_in + $tokens_out
            ]);

            return true;
        } catch (\Exception $e) {
            log_error('USAGE', 'Error logging usage', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant_id,
                'external_id' => $external_id
            ]);
            return false;
        }
    }

    /**
     * Update tenant quota
     */
    private function _update_tenant_quota($tenant_id, $tokens)
    {
        try {
            $db = db_connect();
            
            // Get current quota
            $tenant = $db->table('tenants')
                ->where('tenant_id', $tenant_id)
                ->get()
                ->getRowArray();

            if (!$tenant) {
                throw new \Exception('Tenant not found');
            }

            // Update quota
            $db->table('tenants')
                ->where('tenant_id', $tenant_id)
                ->update([
                    'quota_used' => $tenant['quota_used'] + $tokens,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

        } catch (\Exception $e) {
            log_error('QUOTA', 'Error updating tenant quota', [
                'tenant_id' => $tenant_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Initialize proxy configuration
     */
    private function _initialize_config()
    {
        // Load configuration
        $config = config('Proxy');

        // Set API keys
        $this->api_keys = [
            'openai' => getenv('OPENAI_API_KEY') ?: $config->openaiApiKey,
            'anthropic' => getenv('ANTHROPIC_API_KEY') ?: $config->anthropicApiKey,
            'mistral' => getenv('MISTRAL_API_KEY') ?: $config->mistralApiKey,
            'deepseek' => getenv('DEEPSEEK_API_KEY') ?: $config->deepseekApiKey,
            'google' => getenv('GOOGLE_API_KEY') ?: $config->googleApiKey,
            'azure' => getenv('AZURE_API_KEY') ?: $config->azureApiKey
        ];

        // Set endpoints
        $this->endpoints = [
            'openai' => "https://api.openai.com/v1/chat/completions",
            'anthropic' => getenv('ANTHROPIC_API_ENDPOINT') ?: $config->anthropicEndpoint,
            'mistral' => getenv('MISTRAL_API_ENDPOINT') ?: $config->mistralEndpoint,
            'deepseek' => getenv('DEEPSEEK_API_ENDPOINT') ?: $config->deepseekEndpoint,
            'google' => getenv('GOOGLE_API_ENDPOINT') ?: $config->googleEndpoint,
            'azure' => getenv('AZURE_API_ENDPOINT') ?: $config->azureEndpoint
        ];

        // Set other configuration
        $this->use_simulated_responses = getenv('USE_SIMULATED_RESPONSES') === 'true' || $config->useSimulatedResponses;
        $this->allowed_origins = getenv('ALLOWED_ORIGINS') ?: $config->allowedOrigins;
    }

    /**
     * Get LLM provider instance
     */
    private function _get_llm_provider($provider)
    {
        // Verificar que el provider tenga API key configurada
        if (empty($this->api_keys[$provider])) {
            log_message('error', 'API key not configured for provider', [
                'provider' => $provider
            ]);
            throw new \Exception('API key not configured for provider');
        }

        switch ($provider) {
            case 'openai':
                return new OpenAiProvider($this->api_keys['openai'], $this->endpoints['openai']);
            case 'anthropic':
                return new AnthropicProvider($this->api_keys['anthropic'], $this->endpoints['anthropic']);
            case 'mistral':
                return new MistralProvider($this->api_keys['mistral'], $this->endpoints['mistral']);
            case 'deepseek':
                return new DeepseekProvider($this->api_keys['deepseek'], $this->endpoints['deepseek']);
            case 'google':
                return new GoogleProvider($this->api_keys['google'], $this->endpoints['google']);
            case 'azure':
                return new AzureProvider($this->api_keys['azure'], $this->endpoints['azure']);
            default:
                throw new \Exception("Invalid provider: {$provider}");
        }
    }

    /**
     * Prepare payload for LLM request
     */
    private function _prepare_payload($provider, $model, $messages, $temperature, $stream)
    {
        // Procesar mensajes multimodales
        $processed_messages = array_map(function($message) {
            if (is_array($message['content'])) {
                // Extraer solo el texto de los mensajes multimodales
                $text_parts = [];
                foreach ($message['content'] as $content) {
                    if (isset($content['text'])) {
                        $text_parts[] = $content['text'];
                    }
                }
                return [
                    'role' => $message['role'],
                    'content' => implode("\n", $text_parts)
                ];
            }
            return $message;
        }, $messages);

        // Payload base
        $payload = [
            'model' => $model,
            'messages' => $processed_messages,
            'temperature' => floatval($temperature),
            'stream' => $stream
        ];

        // Ajustar payload según el proveedor
        switch ($provider) {
            case 'openai':
                $payload['max_tokens'] = 2000;
                $payload['frequency_penalty'] = 0;
                $payload['presence_penalty'] = 0;
                break;

            case 'anthropic':
                $payload['max_tokens_to_sample'] = 2000;
                break;

            case 'mistral':
                $payload['max_tokens'] = 2000;
                break;

            case 'deepseek':
                $payload['max_tokens'] = 2000;
                break;

            case 'google':
                $payload['max_output_tokens'] = 2000;
                $payload['temperature'] = min($temperature, 1.0); // Google solo acepta hasta 1.0
                break;

            case 'azure':
                $payload['max_tokens'] = 2000;
                break;
        }

        return $payload;
    }

    /**
     * Get quota information for tenant
     */
    public function quota()
    {
        // Verificar que sea una petición GET
        if (service('request')->getMethod() !== 'get') {
            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(405)
                ->setJSON(['error' => ['message' => 'Method not allowed']]);
        }

        try {
            // Get domain from headers
            $domain = $this->_extract_domain_from_headers();

            // Ensure user exists
            $tenant_id = $this->_ensure_user_exists($domain);

            // Get tenant's quota information
            $db = db_connect();
            
            // Get total tokens used from usage_logs
            $tokens_used = $db->table('usage_logs')
                ->selectSum('tokens_in + tokens_out', 'total_tokens')
                ->where('tenant_id', $tenant_id)
                ->get()
                ->getRow();

            // Get tenant info
            $tenant = $db->table('tenants')
                ->where('tenant_id', $tenant_id)
                ->get()
                ->getRowArray();

            if (!$tenant) {
                throw new \Exception('Tenant not found');
            }

            // Calculate quota
            $quota_used = (int)($tokens_used->total_tokens ?? 0);
            $quota_limit = 1000000; // 1M tokens by default

            // Return quota information
            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'tenant_id' => $tenant_id,
                    'domain' => $domain,
                    'quota_used' => $quota_used,
                    'quota_limit' => $quota_limit,
                    'quota_remaining' => $quota_limit - $quota_used
                ]
            ]);

        } catch (\Exception $e) {
            log_error('QUOTA', 'Error getting quota information', [
                'domain' => $domain ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Install/update database tables
     */
    public function install()
    {
        try {
            // Run migrations
            $migrate = \Config\Services::migrations();
            
            // Run migrations
            if ($migrate->latest() === false) {
                throw new \Exception('Error running migrations');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Database tables created/updated successfully'
            ]);

        } catch (\Exception $e) {
            log_error('INSTALL', 'Error installing database tables', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => [
                    'message' => 'Error installing database tables',
                    'details' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * Get proxy status
     */
    public function status()
    {
        $db = db_connect();

        // Verificar si las API keys están configuradas
        $api_keys_status = [];
        foreach ($this->api_keys as $provider => $key) {
            $api_keys_status[$provider] = !empty($key);
        }

        // Get database status
        $db_status = false;
        try {
            $db->query('SELECT 1');
            $db_status = true;
        } catch (\Exception $e) {
            log_error('STATUS', 'Database connection error', [
                'error' => $e->getMessage()
            ]);
        }

        // Get usage statistics
        $stats = [
            'total_requests' => 0,
            'total_tokens' => 0,
            'active_tenants' => 0,
            'total_buttons' => 0
        ];

        try {
            $stats['total_requests'] = $db->table('usage_logs')->countAll();
            $stats['total_tokens'] = $db->table('usage_logs')
                ->selectSum('tokens_in')
                ->selectSum('tokens_out')
                ->get()
                ->getRowArray();
            $stats['active_tenants'] = $db->table('tenants')->countAll();
            $stats['total_buttons'] = $db->table('buttons')->countAll();
        } catch (\Exception $e) {
            log_error('STATUS', 'Error getting statistics', [
                'error' => $e->getMessage()
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'version' => '1.0.0',
                'environment' => ENVIRONMENT,
                'database' => [
                    'connected' => $db_status,
                    'driver' => $db->DBDriver,
                    'version' => $db->getVersion()
                ],
                'providers' => $api_keys_status,
                'stats' => $stats,
                'memory_usage' => [
                    'current' => memory_get_usage(true),
                    'peak' => memory_get_peak_usage(true)
                ]
            ]
        ]);
    }

    /**
     * Test connection to LLM providers
     */
    public function test_connection()
    {
        $results = [];

        foreach ($this->api_keys as $provider => $key) {
            if (empty($key)) {
                $results[$provider] = [
                    'status' => 'skipped',
                    'message' => 'No API key configured'
                ];
                continue;
            }

            try {
                $llm = $this->_get_llm_provider($provider);
                $response = $llm->process_request(
                    $provider === 'openai' ? 'gpt-3.5-turbo' : 'claude-2',
                    [['role' => 'user', 'content' => 'test']]
                );

                $results[$provider] = [
                    'status' => 'success',
                    'message' => 'Connection successful'
                ];

            } catch (\Exception $e) {
                $results[$provider] = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => $results
        ]);
    }
}
