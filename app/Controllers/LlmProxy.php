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
            $raw_input = file_get_contents('php://input');
            log_message('error', '[PROXY] Raw request data: ' . print_r([
                'raw_input' => $raw_input,
                'content_type' => $this->request->getHeaderLine('Content-Type'),
                'method' => $this->request->getMethod(),
                'headers' => $this->request->headers()
            ], true));

            $json = $this->request->getJSON();
            if (!$json) {
                throw new \Exception('Invalid request format');
            }

            log_debug('PROXY', 'Request data received', [
                'request_id' => $request_id,
                'json' => json_encode($json),
                'raw_input' => $raw_input,
                'content_type' => $this->request->getHeaderLine('Content-Type')
            ]);

            // Extract request parameters
            $provider = $json->provider ?? 'openai';
            $model = $json->model ?? null;
            $messages = $json->messages ?? [];
            $options = $json->options ?? [];
            $stream = $json->stream ?? false;
            $external_id = $json->userId ?? $json->user_id ?? null; // Aceptar tanto userId como user_id
            $button_id = $json->buttonId ?? $json->button_id ?? null; // Aceptar tanto buttonId como button_id

            // --- FLEXIBILIDAD DE TEMPERATURE ---
            // Permitir temperature en raíz o en options
            if (isset($json->temperature)) {
                $options = (array)$options;
                $options['temperature'] = $json->temperature;
            }
            // --- FIN FLEXIBILIDAD TEMPERATURE ---

            // --- FLEXIBILIDAD SYSTEM PROMPT ---
            if (isset($json->systemPrompt)) {
                $has_system = false;
                foreach ($messages as $msg) {
                    if (isset($msg->role) && $msg->role === 'system') {
                        $has_system = true;
                        break;
                    }
                }
                if (!$has_system) {
                    // Insertar systemPrompt como primer mensaje system
                    array_unshift($messages, (object)[
                        'role' => 'system',
                        'content' => $json->systemPrompt
                    ]);
                }
            }
            // --- FIN FLEXIBILIDAD SYSTEM PROMPT ---

            // --- FLEXIBILIDAD IMAGEN ---
            if (isset($json->image) && $json->image) {
                $has_image_in_messages = false;
                foreach ($messages as $msg) {
                    if (isset($msg->content) && is_array($msg->content)) {
                        foreach ($msg->content as $part) {
                            if ((is_object($part) && isset($part->type) && $part->type === 'image_url') ||
                                (is_string($part) && (preg_match('/^data:image\//', $part) || preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $part)))) {
                                $has_image_in_messages = true;
                                break 2;
                            }
                        }
                    }
                }
                if (!$has_image_in_messages) {
                    // Insertar imagen en el primer mensaje de usuario, o crear uno
                    $image_part = null;
                    if (is_string($json->image)) {
                        if (preg_match('/^data:image\//', $json->image)) {
                            // Base64
                            $image_part = (object)[ 'type' => 'image_url', 'image_url' => $json->image ];
                        } elseif (preg_match('/^https?:\/\//', $json->image)) {
                            // URL
                            $image_part = (object)[ 'type' => 'image_url', 'image_url' => $json->image ];
                        }
                    }
                    if ($image_part) {
                        // Buscar primer mensaje de usuario
                        $user_found = false;
                        foreach ($messages as &$msg) {
                            if (isset($msg->role) && $msg->role === 'user') {
                                // Si el content ya es array, agregar la imagen
                                if (is_array($msg->content)) {
                                    $msg->content[] = $image_part;
                                } else {
                                    // Si el content es string, convertirlo en array multimodal
                                    $msg->content = [$msg->content, $image_part];
                                }
                                $user_found = true;
                                break;
                            }
                        }
                        unset($msg);
                        // Si no hay mensaje de usuario, crear uno
                        if (!$user_found) {
                            $messages[] = (object)[
                                'role' => 'user',
                                'content' => [$image_part]
                            ];
                        }
                    }
                }
            }
            // --- FIN FLEXIBILIDAD IMAGEN ---

            // --- ANTEPONER CONTEXTO AL PRIMER MENSAJE DE USUARIO ---
            if (isset($json->context) && $json->context) {
                $context = trim($json->context);
                $user_found = false;
                foreach ($messages as &$msg) {
                    if (isset($msg->role) && $msg->role === 'user') {
                        // Si el contenido es array multimodal
                        if (is_array($msg->content)) {
                            // Anteponer el contexto como string al inicio del array
                            array_unshift($msg->content, $context);
                        } else {
                            // Si el contenido es string, anteponer el contexto
                            $msg->content = $context . "\n" . $msg->content;
                        }
                        $user_found = true;
                        break;
                    }
                }
                unset($msg);
                // Si no hay mensaje de usuario, crear uno
                if (!$user_found) {
                    $messages[] = (object)[
                        'role' => 'user',
                        'content' => $context
                    ];
                }
            }
            // --- FIN ANTEPONER CONTEXTO ---

            // --- VALIDACIÓN DE IMÁGENES Y MODELOS MULTIMODAL ---
            $multimodal_models = [
                // OpenAI
                'gpt-4o',
                'gpt-4-vision-preview',
                'gpt-4-vision',
                'gpt-4-turbo-vision',
                // Google Gemini (multimodal)
                'gemini-1.5-pro-latest',
                'gemini-1.0-pro',
                'gemini-1.0-pro-vision',
                // Anthropic Claude 3 (multimodal)
                'claude-3-opus-20240229',
                'claude-3-sonnet-20240229',
                'claude-3-haiku-20240307',
                'claude-3-7-sonnet-20250219',
                // Futuro: Mistral (si lanzan multimodal), otros proveedores...
            ];
            $has_image = false;
            foreach ($messages as $msg) {
                if (isset($msg->content) && is_array($msg->content)) {
                    foreach ($msg->content as $part) {
                        if (is_object($part) && isset($part->type) && $part->type === 'image_url') {
                            $has_image = true;
                            break 2;
                        }
                        // Opcional: detectar base64/URL
                        if (is_string($part) && (preg_match('/^data:image\//', $part) || preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $part))) {
                            $has_image = true;
                            break 2;
                        }
                    }
                }
            }
            if ($has_image && (!in_array(strtolower($model), $multimodal_models))) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error' => 'El modelo o proveedor seleccionado no soporta imágenes. Usa un modelo multimodal como gpt-4-vision-preview, gpt-4o o gemini-1.0-pro-vision.'
                ]);
            }
            // --- FIN VALIDACIÓN DE IMÁGENES ---

            log_message('error', '[PROXY] Extracted parameters: ' . print_r([
                'provider' => $provider,
                'model' => $model,
                'messages_count' => count($messages),
                'stream' => $stream,
                'external_id' => $external_id,
                'button_id' => $button_id,
                'json' => json_encode($json)
            ], true));

            log_debug('PROXY', 'Extracted parameters', [
                'request_id' => $request_id,
                'provider' => $provider,
                'model' => $model,
                'messages_count' => count($messages),
                'stream' => $stream,
                'external_id' => $external_id,
                'button_id' => $button_id
            ]);

            // Validate required parameters
            if (!$model || empty($messages)) {
                throw new \Exception('Missing required parameters: model and messages are required');
            }

            if (!$external_id) {
                throw new \Exception('Missing user_id in request');
            }

            if (!$button_id) {
                throw new \Exception('Missing button_id in request');
            }

            // Get domain from headers
            $domain = $this->_extract_domain_from_headers();
            log_debug('PROXY', 'Domain extracted', [
                'domain' => $domain,
                'request_id' => $request_id
            ]);

            // Get button configuration
            $db = db_connect();
            $button = $db->table('buttons')
                ->where('button_id', $button_id)
                ->where('status', 'active')
                ->get()
                ->getRowArray();

            if (!$button) {
                log_error('PROXY', 'Button not found or inactive', [
                    'button_id' => $button_id,
                    'tenant_id' => $button['tenant_id']
                ]);
                throw new \Exception('Invalid or inactive button');
            }

            // Store the actual button_id from database
            $actual_button_id = $button['button_id'];

            // Validate domain
            if ($button['domain'] !== '*' && $this->_normalize_domain($button['domain']) !== $this->_normalize_domain($domain)) {
                log_error('PROXY', 'Domain mismatch', [
                    'request_domain' => $domain,
                    'normalized_request_domain' => $this->_normalize_domain($domain),
                    'button_domain' => $button['domain'],
                    'normalized_button_domain' => $this->_normalize_domain($button['domain']),
                    'origin' => service('request')->getHeaderLine('Origin'),
                    'referer' => service('request')->getHeaderLine('Referer'),
                    'button' => $button
                ]);
                throw new \Exception('Invalid domain for this button');
            }

            // Get API user
            $api_user = $db->table('api_users')
                ->where('tenant_id', $button['tenant_id'])
                ->where('external_id', $external_id)
                ->where('active', 1)
                ->get()
                ->getRowArray();

            if (!$api_user) {
                log_error('PROXY', 'API user not found or inactive', [
                    'tenant_id' => $button['tenant_id'],
                    'external_id' => $external_id
                ]);
                throw new \Exception('Invalid or inactive API user');
            }

            // Use button configuration
            $provider = $button['provider'];
            $model = $button['model'];

            // Process the request
            $response = $this->_process_llm_request(
                $provider,
                $model,
                $messages,
                $options->temperature ?? 0.7,
                $stream,
                $button['tenant_id'],
                $external_id,
                $actual_button_id
            );

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
                $this->_log_usage($tenant_id, $external_id, $button_id, $provider, $model, $usage['tokens_in'] + $usage['tokens_out'], null, $has_image);

                // Return empty response since we've already sent the stream
                return '';
            } else {
                $response = $llm->process_request($model, $messages, $options);

                // Log usage
                $this->_log_usage($tenant_id, $external_id, $button_id, $provider, $model, $response['tokens_in'] + $response['tokens_out'], null, $has_image);

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
    private function _log_usage($tenant_id, $external_id, $button_id, $provider, $model, $total_tokens, $cost = null, $has_image = false)
    {
        try {
            $db = db_connect();

            // Generate usage_id
            helper('hash');
            $usage_id = generate_hash_id('usage');

            // Calculate cost if not provided
            if ($cost === null) {
                $cost = $this->_calculate_cost($provider, $model, $total_tokens);
            }

            $data = [
                'usage_id' => $usage_id,
                'tenant_id' => $tenant_id,
                'user_id' => $external_id,
                'external_id' => $external_id,
                'button_id' => $button_id,
                'provider' => $provider,
                'model' => $model,
                'tokens' => $total_tokens,
                'cost' => $cost ?? 0,
                'has_image' => $has_image ? 1 : 0,
                'status' => 'success',
                'created_at' => date('Y-m-d H:i:s')
            ];

            log_debug('USAGE', 'Intentando insertar log', [
                'data' => $data
            ]);

            $result = $db->table('usage_logs')->insert($data);

            if (!$result) {
                $error = $db->error();
                log_error('USAGE', 'Error al insertar log', [
                    'error' => $error,
                    'last_query' => $db->getLastQuery() ? $db->getLastQuery()->getQuery() : 'No query available'
                ]);
                return false;
            }

            $usage_log_id = $db->insertID();

            // Extraer system prompt y messages del request
            $request = $this->request->getJSON();
            if ($request && isset($request->messages)) {
                $system_prompt = null;
                foreach ($request->messages as $msg) {
                    if ($msg->role === 'system') {
                        $system_prompt = $msg->content;
                        break;
                    }
                }

                // Grabar en prompt_logs
                $prompt_data = [
                    'usage_log_id' => $usage_log_id,
                    'tenant_id' => $tenant_id,
                    'button_id' => $button_id,
                    'messages' => json_encode($request->messages),
                    'system_prompt' => $system_prompt,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $result = $db->table('prompt_logs')->insert($prompt_data);
                if (!$result) {
                    log_error('USAGE', 'Error al insertar prompt log', [
                        'error' => $db->error(),
                        'data' => $prompt_data
                    ]);
                }
            }

            log_debug('USAGE', 'Log insertado correctamente', [
                'usage_id' => $usage_id,
                'insert_id' => $usage_log_id
            ]);

            return true;
        } catch (\Exception $e) {
            log_error('USAGE', 'Excepción al insertar log', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

        // Limpiar el dominio de la misma manera que en Domains.php
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);

        return $domain;
    }

    /**
     * Normaliza un dominio para comparación
     */
    private function _normalize_domain($domain)
    {
        // Si el dominio es '*', retornarlo tal cual
        if ($domain === '*') {
            return $domain;
        }

        // Remover protocolo y www si existen
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);

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
