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

            // Get LLM provider instance
            $llm = $this->_get_llm_provider($provider);

            // Process request through provider
            $response = $llm->process_request($model, $messages, array_merge($options, ['stream' => $stream]));

            // Log usage
            $this->_log_usage($tenant_id, $domain, $provider, $model, $response['tokens_in'], $response['tokens_out'], $messages, $response['response'], $button_id);

            // Return successful response
            return $this->response->setJSON([
                'success' => true,
                'data' => $response
            ]);

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
        try {
            $db = db_connect();
            
            // Try to directly check if user exists in the database
            $tenant = $db->table('tenants')
                ->where('domain', $domain)
                ->get()
                ->getRowArray();

            if (!$tenant) {
                // Create new tenant
                $tenant_id = 'ten-' . dechex(time()) . '-' . bin2hex(random_bytes(4));
                $db->table('tenants')->insert([
                    'tenant_id' => $tenant_id,
                    'domain' => $domain,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                return $tenant_id;
            }

            return $tenant['tenant_id'];

        } catch (\Exception $e) {
            log_error('USER', 'Error ensuring user exists', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Log usage for a request
     */
    private function _log_usage($tenant_id, $domain, $provider, $model, $tokens_in, $tokens_out, $messages, $response, $button_id = null)
    {
        try {
            $db = db_connect();

            // Insert usage log
            $usage_log = [
                'tenant_id' => $tenant_id,
                'domain' => $domain,
                'provider' => $provider,
                'model' => $model,
                'tokens_in' => $tokens_in,
                'tokens_out' => $tokens_out,
                'button_id' => $button_id,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $db->table('usage_logs')->insert($usage_log);
            $usage_log_id = $db->insertID();

            // Insert prompt log
            $prompt_log = [
                'usage_log_id' => $usage_log_id,
                'messages' => json_encode($messages),
                'response' => $response,
                'created_at' => date('Y-m-d H:i:s')
            ];

            // If button_id is provided, get system prompt from button
            if ($button_id) {
                $button = $db->table('buttons')
                    ->where('button_id', $button_id)
                    ->get()
                    ->getRowArray();
                if ($button) {
                    $prompt_log['system_prompt'] = $button['system_prompt'];
                    $prompt_log['system_prompt_source'] = 'button';
                }
            }

            $db->table('prompt_logs')->insert($prompt_log);

            // Update tenant quota
            $this->_update_tenant_quota($tenant_id, $tokens_in + $tokens_out);

        } catch (\Exception $e) {
            log_error('USAGE', 'Error logging usage', [
                'tenant_id' => $tenant_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
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
            'openai' => getenv('OPENAI_API_ENDPOINT') ?: $config->openaiEndpoint,
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
}
