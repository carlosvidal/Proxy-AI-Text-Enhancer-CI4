<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\LlmProxyModel;
use App\Models\UsageLogsModel;
use App\Libraries\LlmProviders\OpenAiProvider;
use App\Libraries\LlmProviders\AnthropicProvider;
use App\Libraries\LlmProviders\MistralProvider;
use App\Libraries\LlmProviders\DeepseekProvider;
use App\Libraries\LlmProviders\GoogleProvider;
use App\Libraries\LlmProviders\AzureProvider;

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
        helper(['url', 'form', 'logger', 'jwt', 'api_key', 'image_proxy', 'hash']);

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
            $external_id = $json->userId ?? $json->user_id ?? null; // Aceptar tanto userId como user_id

            // Validate required parameters
            if (!$model || empty($messages)) {
                throw new \Exception('Missing required parameters: model and messages are required');
            }

            if (!$external_id) {
                throw new \Exception('Missing user_id in request');
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
            $response = $this->_process_llm_request($provider, $model, $messages, $options->temperature ?? 0.7, $stream, $tenant_id, $external_id, $button_id);

            return $response;

        } catch (\Exception $e) {
            // Log error
            log_message('error', 'Error processing LLM request: ' . $e->getMessage(), [
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
            $tenant_id = generate_hash_id('ten');
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
     * Procesa la solicitud al LLM
     */
    private function _process_llm_request($provider, $model, $messages, $temperature, $stream, $tenant_id, $external_id, $button_id = null, $has_image = false)
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
                $this->_log_usage($tenant_id, $external_id, $provider, $model, $usage['tokens_in'], $usage['tokens_out'], $button_id, $has_image);

                // Return empty response since we've already sent the stream
                return '';

            } else {
                $response = $llm->process_request($model, $messages, $options);

                // Log usage
                $this->_log_usage($tenant_id, $external_id, $provider, $model, $response['tokens_in'], $response['tokens_out'], $button_id, $has_image);

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
     * Log usage for billing purposes
     */
    private function _log_usage($tenant_id, $external_id, $provider, $model, $tokens_in, $tokens_out, $button_id = null, $has_image = false)
    {
        try {
            $usageModel = new UsageLogsModel();
            
            // Calculate total tokens
            $total_tokens = $tokens_in + $tokens_out;
            
            // Calculate cost based on model
            $cost = $this->_calculate_cost($provider, $model, $total_tokens);
            
            // Generate usage_id using hash helper
            $usage_id = generate_hash_id('usage');

            // Find user_id from tenant_users table if external_id is provided
            $user_id = null;
            if ($external_id) {
                $db = db_connect();
                $user = $db->table('tenant_users')
                    ->where('tenant_id', $tenant_id)
                    ->where('external_id', $external_id)
                    ->get()
                    ->getRowArray();

                if ($user) {
                    $user_id = $user['id'];
                }
            }
            
            $data = [
                'usage_id' => $usage_id,
                'tenant_id' => $tenant_id,
                'user_id' => $user_id,
                'external_id' => $external_id,
                'button_id' => $button_id,
                'provider' => $provider,
                'model' => $model,
                'tokens' => $total_tokens,
                'cost' => $cost,
                'has_image' => $has_image ? 1 : 0,
                'status' => 'success'
            ];
            
            if (!$usageModel->insert($data)) {
                log_error('USAGE', 'Error logging usage', [
                    'error' => implode(', ', $usageModel->errors()),
                    'tenant_id' => $tenant_id,
                    'external_id' => $external_id,
                    'button_id' => $button_id
                ]);
                return false;
            }
            
            log_debug('USAGE', 'Usage logged successfully', $data);
            return true;
            
        } catch (\Exception $e) {
            log_error('USAGE', 'Error logging usage', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant_id,
                'external_id' => $external_id,
                'button_id' => $button_id
            ]);
            return false;
        }
    }

    /**
     * Calculate cost based on provider and model
     */
    private function _calculate_cost($provider, $model, $tokens)
    {
        $rates = [
            'openai' => [
                'gpt-4-turbo' => 0.00003,
                'gpt-4-vision' => 0.00003,
                'gpt-3.5-turbo' => 0.000002,
                'default' => 0.00001
            ],
            'anthropic' => [
                'claude-3-opus-20240229' => 0.00015,
                'claude-3-sonnet-20240229' => 0.00003,
                'claude-3-haiku-20240307' => 0.00001,
                'default' => 0.00003
            ],
            'mistral' => [
                'mistral-large' => 0.00002,
                'default' => 0.00001
            ],
            'default' => 0.00001
        ];
        
        $provider_rates = $rates[$provider] ?? $rates['default'];
        $rate = $provider_rates[$model] ?? $provider_rates['default'];
        
        return round($tokens * $rate, 4);
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
     * Get LLM provider instance
     */
    private function _get_llm_provider($provider)
    {
        if (!isset($this->api_keys[$provider]) || empty($this->api_keys[$provider])) {
            throw new \Exception('Provider not configured: ' . $provider);
        }

        switch ($provider) {
            case 'openai':
                return new OpenAiProvider($this->api_keys[$provider], $this->endpoints[$provider]);
            case 'anthropic':
                return new AnthropicProvider($this->api_keys[$provider], $this->endpoints[$provider]);
            case 'mistral':
                return new MistralProvider($this->api_keys[$provider], $this->endpoints[$provider]);
            case 'deepseek':
                return new DeepseekProvider($this->api_keys[$provider], $this->endpoints[$provider]);
            case 'google':
                return new GoogleProvider($this->api_keys[$provider], $this->endpoints[$provider]);
            case 'azure':
                return new AzureProvider($this->api_keys[$provider], $this->endpoints[$provider]);
            default:
                throw new \Exception('Unsupported provider: ' . $provider);
        }
    }

    /**
     * Initialize proxy configuration
     */
    private function _initialize_config()
    {
        $this->api_keys = [
            'openai' => env('OPENAI_API_KEY', ''),
            'anthropic' => env('ANTHROPIC_API_KEY', ''),
            'mistral' => env('MISTRAL_API_KEY', ''),
            'deepseek' => env('DEEPSEEK_API_KEY', ''),
            'google' => env('GOOGLE_API_KEY', ''),
            'azure' => env('AZURE_API_KEY', '')
        ];

        $this->endpoints = [
            'openai' => env('OPENAI_API_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
            'anthropic' => env('ANTHROPIC_API_ENDPOINT', 'https://api.anthropic.com/v1/messages'),
            'mistral' => env('MISTRAL_API_ENDPOINT', 'https://api.mistral.ai/v1/chat/completions'),
            'deepseek' => env('DEEPSEEK_API_ENDPOINT', 'https://api.deepseek.com/v1/chat/completions'),
            'google' => env('GOOGLE_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1/models'),
            'azure' => env('AZURE_API_ENDPOINT', '')
        ];

        $this->use_simulated_responses = env('USE_SIMULATED_RESPONSES', false);
        $this->allowed_origins = env('ALLOWED_ORIGINS', '*');
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
}
