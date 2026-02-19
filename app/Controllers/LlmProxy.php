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

        // Ensure CORS headers are set for all responses
        $this->_set_cors_headers();

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
            $messages = $json->messages ?? [];
            $options = $json->options ?? [];
            $stream = $json->stream ?? false;
            $external_id = $json->userId ?? $json->user_id ?? null; // Aceptar tanto userId como user_id
            $button_id = $json->buttonId ?? $json->button_id ?? null; // Aceptar tanto buttonId como button_id

            // Get provider and model from button configuration in database
            $provider = $json->provider ?? 'openai'; // Fallback por compatibilidad
            $model = $json->model ?? null; // Fallback por compatibilidad
            
            if ($button_id) {
                try {
                    $db = \Config\Database::connect();
                    
                    // Debug: Check all buttons for this tenant
                    $allButtonsQuery = $db->query("SELECT button_id, status, provider, model FROM buttons WHERE tenant_id = ?", [$json->tenantId ?? 'unknown']);
                    $allButtons = $allButtonsQuery ? $allButtonsQuery->getResultArray() : [];
                    
                    log_debug('PROXY', 'All buttons for tenant', [
                        'tenant_id' => $json->tenantId ?? 'unknown',
                        'requested_button_id' => $button_id,
                        'all_buttons' => $allButtons
                    ]);
                    
                    $buttonQuery = $db->query("
                        SELECT provider, model, api_key_id 
                        FROM buttons 
                        WHERE button_id = ? AND status = 'active'
                    ", [$button_id]);
                    
                    if ($buttonQuery && $buttonRow = $buttonQuery->getRowArray()) {
                        $provider = $buttonRow['provider'];
                        $model = $buttonRow['model'];
                        
                        log_debug('PROXY', 'Button configuration loaded from database', [
                            'button_id' => $button_id,
                            'provider' => $provider,
                            'model' => $model,
                            'api_key_id' => $buttonRow['api_key_id']
                        ]);
                    } else {
                        log_error('PROXY', 'Button not found or inactive', [
                            'button_id' => $button_id,
                            'tenant_id' => $json->tenantId ?? 'unknown'
                        ]);
                        throw new \Exception('Button not found or inactive: ' . $button_id);
                    }
                } catch (\Exception $e) {
                    log_error('PROXY', 'Error loading button configuration', [
                        'button_id' => $button_id,
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception('Error loading button configuration: ' . $e->getMessage());
                }
            }

            // --- SOPORTE PARA NUEVO PAYLOAD: prompt, content, context ---
            if ((empty($messages) || count($messages) === 0) && (isset($json->prompt) || isset($json->content) || isset($json->context))) {
                $user_content = '';
                if (isset($json->context) && $json->context) {
                    $user_content .= trim($json->context) . "\n";
                }
                if (isset($json->content) && $json->content) {
                    $user_content .= trim($json->content) . "\n";
                }
                if (isset($json->prompt) && $json->prompt) {
                    $user_content .= trim($json->prompt);
                }
                $messages[] = (object)[
                    'role' => 'user',
                    'content' => trim($user_content)
                ];
            }
            // --- FIN SOPORTE NUEVO PAYLOAD ---

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

            log_error('PROXY', 'CHECKPOINT 1: After parameter extraction');

            log_debug('PROXY', 'About to validate parameters and get button configuration', [
                'request_id' => $request_id,
                'button_id' => $button_id,
                'tenant_id' => $json->tenantId ?? 'missing'
            ]);

            // Validate required parameters
            log_debug('PROXY', 'Validating required parameters', [
                'provider' => $provider,
                'model' => $model,
                'messages_count' => count($messages),
                'external_id' => $external_id,
                'button_id' => $button_id
            ]);

            if (!$provider) {
                log_error('PROXY', 'Missing provider parameter');
                throw new \Exception('Missing required parameter: provider is required');
            }
            
            if (!$model || empty($messages)) {
                log_error('PROXY', 'Missing model or messages', [
                    'model' => $model,
                    'messages_count' => count($messages)
                ]);
                throw new \Exception('Missing required parameters: model and messages are required');
            }

            if (!$external_id) {
                log_error('PROXY', 'Missing external_id parameter');
                throw new \Exception('Missing user_id in request');
            }

            if (!$button_id) {
                log_error('PROXY', 'Missing button_id parameter');
                throw new \Exception('Missing button_id in request');
            }

            log_debug('PROXY', 'All required parameters validated successfully');
            log_error('PROXY', 'CHECKPOINT 2: After parameter validation');

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
                    'button_id' => $button_id
                ]);
                throw new \Exception('Invalid or inactive button');
            }

            // Store the actual button_id from database
            $actual_button_id = $button['button_id'];

            // Validate domain
            $tenant_id = $button['tenant_id'];
            $is_domain_allowed = false;
            $all_allowed_domains = [];

            if ($button['domain'] === '__tenant__') {
                // Button allows all tenant domains — check against the domains table
                $tenant_domains = $db->table('domains')
                    ->select('domain')
                    ->where('tenant_id', $tenant_id)
                    ->get()
                    ->getResultArray();

                foreach ($tenant_domains as $row) {
                    $d = trim($row['domain']);
                    if (!empty($d)) {
                        $all_allowed_domains[] = $d;
                        if ($this->_normalize_domain($d) === $this->_normalize_domain($domain)) {
                            $is_domain_allowed = true;
                        }
                    }
                }
            } else {
                // Button has specific domain(s) — check only those
                $button_domains = explode(',', $button['domain']);
                foreach ($button_domains as $d) {
                    $d = trim($d);
                    if (!empty($d)) {
                        $all_allowed_domains[] = $d;
                        if ($this->_normalize_domain($d) === $this->_normalize_domain($domain)) {
                            $is_domain_allowed = true;
                        }
                    }
                }
            }

            if (!$is_domain_allowed) {
                log_error('PROXY', 'Domain mismatch', [
                    'request_domain' => $domain,
                    'normalized_request_domain' => $this->_normalize_domain($domain),
                    'tenant_id' => $tenant_id,
                    'button_domain_mode' => $button['domain'] === '__tenant__' ? 'tenant-level' : 'button-specific',
                    'allowed_domains' => array_unique($all_allowed_domains),
                    'origin' => service('request')->getHeaderLine('Origin'),
                    'referer' => service('request')->getHeaderLine('Referer'),
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

            // Si no existe, intentar crear si el botón lo permite
            if (!$api_user) {
                if (!empty($button['auto_create_api_users'])) {
                    log_message('info', '[PROXY] AUTO-CREATE ENABLED - Starting auto-creation process for external_id: ' . $external_id);
                    
                    // Crear usuario API en caliente usando el modelo
                    $apiUsersModel = new \App\Models\ApiUsersModel();
                    
                    $userData = [
                        'tenant_id' => $button['tenant_id'],
                        'external_id' => $external_id,
                        'active' => 1,
                        'quota' => 1000000
                    ];
                    
                    $insertResult = $apiUsersModel->insert($userData);
                    
                    if (!$insertResult) {
                        $modelErrors = $apiUsersModel->errors();
                        log_message('error', '[PROXY] Error al crear usuario API automáticamente | Errors: ' . json_encode($modelErrors) . ' | UserData: ' . json_encode($userData));
                        throw new \Exception('No se pudo crear el usuario API automáticamente: ' . json_encode($modelErrors));
                    }
                    
                    log_message('info', '[PROXY] User created successfully with user_id: ' . $insertResult);
                    
                    $api_user = $db->table('api_users')
                        ->where('tenant_id', $button['tenant_id'])
                        ->where('external_id', $external_id)
                        ->where('active', 1)
                        ->get()
                        ->getRowArray();
                    log_message('info', '[PROXY] API user auto-created for external_id=' . $external_id . ' en tenant=' . $button['tenant_id']);
                }
            }

            if (!$api_user) {
                log_error('PROXY', 'API user not found or inactive', [
                    'tenant_id' => $button['tenant_id'],
                    'external_id' => $external_id
                ]);
                throw new \Exception('Invalid or inactive API user');
            }

            // Check quota before processing
            $quotaCheck = $this->_check_user_quota($api_user, $button['tenant_id']);
            if (!$quotaCheck['allowed']) {
                throw new \Exception($quotaCheck['message']);
            }
            
            // Store quota info for response
            $quotaInfo = $quotaCheck;

            // Use button configuration
            $provider = $button['provider'];
            $model = $button['model'];

            log_error('PROXY', 'CHECKPOINT 3: About to process LLM request', [
                'provider' => $provider,
                'model' => $model,
                'tenant_id' => $button['tenant_id']
            ]);

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
            // Get button configuration to find the assigned API key
            $button_api_key_id = null;
            if ($button_id) {
                $buttonsModel = new \App\Models\ButtonsModel();
                $button = $buttonsModel->where('button_id', $button_id)
                                     ->where('tenant_id', $tenant_id)
                                     ->first();
                
                if ($button && !empty($button['api_key_id'])) {
                    $button_api_key_id = $button['api_key_id'];
                    log_message('error', '[PROXY] CHECKPOINT 3a: Found button API key ID: ' . $button_api_key_id);
                } else {
                    log_message('error', '[PROXY] CHECKPOINT 3b: No API key assigned to button, will use default');
                }
            }
            
            // Get LLM provider instance
            $llm = $this->_get_llm_provider($provider, $tenant_id, $button_api_key_id);

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

                // Set CORS headers first for streaming
                $this->_set_cors_headers();
                
                // Set headers for streaming
                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache');
                header('Connection: keep-alive');
                header('X-Accel-Buffering: no');

                // Log usage before streaming starts (approximate token count)
                $usage = $llm->get_token_usage($messages);
                $this->_log_usage($tenant_id, $external_id, $button_id, $provider, $model, $usage['tokens_in'] + $usage['tokens_out'], null, $has_image);

                // Get streaming response
                $response = $llm->process_stream_request($model, $messages, $options);

                // Process stream response
                if (is_callable($response)) {
                    log_message('debug', '[PROXY] Starting streaming response execution');
                    $response();
                    log_message('debug', '[PROXY] Streaming response completed');
                } else {
                    log_error('PROXY', 'Invalid stream response', [
                        'provider' => $provider,
                        'type' => gettype($response)
                    ]);
                    throw new \Exception('Invalid stream response from provider');
                }

                // For streaming responses, we need to exit after sending the stream
                // to prevent CodeIgniter from trying to send additional response data
                exit;
            } else {
                $response = $llm->process_request($model, $messages, $options);

                // Log usage
                $this->_log_usage($tenant_id, $external_id, $button_id, $provider, $model, $response['tokens_in'] + $response['tokens_out'], null, $has_image);

                // Update quota info after processing
                $finalQuotaCheck = $this->_check_user_quota($api_user, $button['tenant_id']);
                
                // Return successful response
                return $this->response->setJSON([
                    'success' => true,
                    'response' => $response['response'],
                    'tokens_in' => $response['tokens_in'],
                    'tokens_out' => $response['tokens_out'],
                    'quota' => [
                        'monthly_remaining' => $finalQuotaCheck['monthly_remaining'] ?? 0,
                        'daily_remaining' => $finalQuotaCheck['daily_remaining'] ?? 0,
                        'monthly_used' => $finalQuotaCheck['monthly_used'] ?? 0,
                        'daily_used' => $finalQuotaCheck['daily_used'] ?? 0
                    ]
                ]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $statusCode = 500;
            
            // Check if it's a quota error
            if (strpos($errorMessage, 'quota exceeded') !== false) {
                $statusCode = 429; // Too Many Requests
            }
            
            log_error('PROXY', 'Error processing LLM request', [
                'error' => $errorMessage,
                'status_code' => $statusCode,
                'provider' => $provider ?? 'unknown',
                'model' => $model ?? 'unknown'
            ]);

            return $this->response
                ->setStatusCode($statusCode)
                ->setJSON([
                    'success' => false,
                    'error' => $errorMessage,
                    'error_type' => strpos($errorMessage, 'quota exceeded') !== false ? 'quota_exceeded' : 'server_error'
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
                'user_id' => 0, // Set to 0 since column is NOT NULL but we use external_id for identification
                'external_id' => $external_id,
                'button_id' => $button_id,
                'provider' => $provider,
                'model' => $model,
                'tokens' => (int)$total_tokens, // Ensure it's an integer
                'cost' => (float)($cost ?? 0),
                'has_image' => $has_image ? 1 : 0,
                'status' => 'success'
            ];

            log_message('error', '[USAGE] Intentando insertar log de uso | Data: ' . json_encode($data));

            // Use model instead of direct insert to handle timestamps properly
            $usageModel = new \App\Models\UsageLogsModel();
            $result = $usageModel->insert($data);

            log_message('error', '[USAGE] Insert result: ' . print_r($result, true));

            if (!$result) {
                $modelErrors = $usageModel->errors();
                $error = $db->error();
                log_message('error', '[USAGE] Error al insertar log | DB Error: ' . json_encode($error) . ' | Model Errors: ' . json_encode($modelErrors) . ' | Data: ' . json_encode($data));
                return false;
            } else {
                log_message('error', '[USAGE] Log de uso insertado exitosamente con ID: ' . $result);
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

                // TODO: Crear tabla prompt_logs para logging detallado de prompts
                // Temporalmente comentado hasta crear la tabla
                /*
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
                */
            }

            log_debug('USAGE', 'Log insertado correctamente', [
                'usage_id' => $usage_id,
                'insert_id' => $usage_log_id
            ]);

            return true;
        } catch (\Exception $e) {
            // Use log_message directly to avoid issues during streaming
            log_message('error', '[USAGE] Excepción al insertar log | Error: ' . $e->getMessage() . ' | Trace: ' . substr($e->getTraceAsString(), 0, 500) . ' | Data: ' . json_encode($data ?? []));
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
            'deepseek' => [
                'deepseek-chat' => 0.00000014,  // $0.14 per 1M input tokens
                'deepseek-coder' => 0.00000014,
                'default' => 0.00000014
            ],
            'google' => [
                'gemini-1.5-pro-latest' => 0.0000035,
                'gemini-1.0-pro' => 0.00000025,
                'default' => 0.0000035
            ],
            'default' => 0.00001
        ];

        $provider_rates = $rates[$provider] ?? $rates['default'];

        // Fix: if provider_rates is a scalar (float), treat it as the rate directly
        if (is_array($provider_rates)) {
            $rate = $provider_rates[$model] ?? $provider_rates['default'];
        } else {
            $rate = $provider_rates;
        }

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
            $parsed = parse_url($origin);
            $domain = $parsed['host'];
            if (isset($parsed['port'])) {
                $domain .= ':' . $parsed['port'];
            }
        } elseif (!empty($referer)) {
            $parsed = parse_url($referer);
            $domain = $parsed['host'];
            if (isset($parsed['port'])) {
                $domain .= ':' . $parsed['port'];
            }
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
        // Remover barra final y espacios
        $domain = rtrim(trim($domain), '/');

        return $domain;
    }

    /**
     * Get LLM provider instance
     */
    private function _get_llm_provider($provider, $tenant_id = null, $button_api_key_id = null)
    {
        log_error('PROXY', 'CHECKPOINT 4: Entering _get_llm_provider', [
            'provider' => $provider,
            'tenant_id' => $tenant_id,
            'button_api_key_id' => $button_api_key_id
        ]);
        
        $api_key = null;
        
        // First try to get API key from database if tenant_id is provided
        if ($tenant_id) {
            log_message('error', '[PROXY] CHECKPOINT 5: About to get API key from database for tenant: ' . $tenant_id . ', provider: ' . $provider);
            
            $apiKeysModel = new \App\Models\ApiKeysModel();
            
            log_message('error', '[PROXY] CHECKPOINT 6: ApiKeysModel created successfully');
            
            try {
                // First try to get the specific API key assigned to the button
                if ($button_api_key_id) {
                    log_message('error', '[PROXY] CHECKPOINT 7a: Trying to get specific API key for button: ' . $button_api_key_id);
                    $apiKeyRecord = $apiKeysModel->where('api_key_id', $button_api_key_id)
                                                ->where('tenant_id', $tenant_id)
                                                ->where('active', 1)
                                                ->first();
                    
                    if ($apiKeyRecord) {
                        log_message('error', '[PROXY] CHECKPOINT 7b: Button-specific API key found: ' . $apiKeyRecord['name']);
                    } else {
                        log_message('error', '[PROXY] CHECKPOINT 7c: Button-specific API key not found, falling back to default');
                    }
                }
                
                // Fallback to default API key if no specific key found
                if (!$apiKeyRecord) {
                    $apiKeyRecord = $apiKeysModel->getDefaultKey($tenant_id, $provider);
                    log_message('error', '[PROXY] CHECKPOINT 7d: Default API key lookup completed. Found: ' . (!empty($apiKeyRecord) ? 'YES' : 'NO'));
                }
                
            } catch (\Exception $e) {
                log_error('PROXY', 'Error getting API key from database', [
                    'tenant_id' => $tenant_id,
                    'provider' => $provider,
                    'button_api_key_id' => $button_api_key_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
            
            if ($apiKeyRecord && !empty($apiKeyRecord['api_key'])) {
                $raw_api_key = $apiKeyRecord['api_key'];
                
                // Manual decryption since afterFind is commented out
                if (strlen($raw_api_key) > 100) { // Likely encrypted
                    try {
                        $encrypter = \Config\Services::encrypter();
                        $api_key = $encrypter->decrypt(base64_decode($raw_api_key));
                        log_message('error', '[PROXY] CHECKPOINT 8a: API key decrypted manually. Original length: ' . strlen($raw_api_key) . ', decrypted length: ' . strlen($api_key));
                    } catch (\Exception $e) {
                        log_message('error', '[PROXY] ERROR: Failed to decrypt API key: ' . $e->getMessage());
                        $api_key = $raw_api_key; // Use as-is if decryption fails
                    }
                } else {
                    $api_key = $raw_api_key; // Already decrypted or plain text
                }
                
                log_message('error', '[PROXY] CHECKPOINT 8: API key extracted. Length: ' . strlen($api_key) . ', starts with: ' . substr($api_key, 0, 10));
            } else {
                log_warning('PROXY', 'No default API key found for provider, checking for any active key', [
                    'provider' => $provider,
                    'tenant_id' => $tenant_id
                ]);
                
                // Fallback: try to get any active API key for this provider and tenant
                $anyActiveKey = $apiKeysModel->where('tenant_id', $tenant_id)
                                           ->where('provider', $provider)
                                           ->where('active', 1)
                                           ->first();
                
                if ($anyActiveKey && !empty($anyActiveKey['api_key'])) {
                    $api_key = $anyActiveKey['api_key'];
                    log_info('PROXY', 'Using any active API key for provider', [
                        'provider' => $provider,
                        'tenant_id' => $tenant_id,
                        'api_key_name' => $anyActiveKey['name'] ?? 'Unknown',
                        'api_key_id' => $anyActiveKey['api_key_id'] ?? 'Unknown',
                        'is_default' => $anyActiveKey['is_default'] ?? 'Unknown'
                    ]);
                } else {
                    // Debug: show all available keys for this tenant
                    $allKeys = $apiKeysModel->where('tenant_id', $tenant_id)->where('active', 1)->findAll();
                    log_warning('PROXY', 'No API key found for provider, falling back to environment', [
                        'provider' => $provider,
                        'tenant_id' => $tenant_id,
                        'all_active_keys_count' => count($allKeys),
                        'all_active_keys' => array_map(function($key) {
                            return [
                                'api_key_id' => $key['api_key_id'],
                                'provider' => $key['provider'],
                                'name' => $key['name'],
                                'is_default' => $key['is_default']
                            ];
                        }, $allKeys)
                    ]);
                }
            }
        }
        
        // Fallback to environment variables if no database key found
        if (!$api_key) {
            if (!isset($this->api_keys[$provider]) || empty($this->api_keys[$provider])) {
                throw new \Exception('Provider not configured: ' . $provider . ' (no API key found in database or environment)');
            }
            $api_key = $this->api_keys[$provider];
            log_debug('PROXY', 'Using environment API key for provider', [
                'provider' => $provider
            ]);
        }

        log_message('error', '[PROXY] CHECKPOINT 9: About to create provider instance. Provider: ' . $provider . ', API key length: ' . strlen($api_key));

        switch ($provider) {
            case 'openai':
                return new OpenAiProvider($api_key, $this->endpoints[$provider]);
            case 'anthropic':
                return new AnthropicProvider($api_key, $this->endpoints[$provider]);
            case 'mistral':
                return new MistralProvider($api_key, $this->endpoints[$provider]);
            case 'deepseek':
                return new DeepseekProvider($api_key, $this->endpoints[$provider]);
            case 'google':
                return new GoogleProvider($api_key, $this->endpoints[$provider]);
            case 'azure':
                return new AzureProvider($api_key, $this->endpoints[$provider]);
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
            'openai' => env('OPENAI_API_ENDPOINT', 'https://api.openai.com'),
            'anthropic' => env('ANTHROPIC_API_ENDPOINT', 'https://api.anthropic.com'),
            'mistral' => env('MISTRAL_API_ENDPOINT', 'https://api.mistral.ai'),
            'deepseek' => env('DEEPSEEK_API_ENDPOINT', 'https://api.deepseek.com'),
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
        $this->_set_cors_headers();
        return $this->response
            ->setStatusCode(200);
    }

    /**
     * Set CORS headers for all responses
     */
    private function _set_cors_headers()
    {
        $origin = service('request')->getHeaderLine('Origin');

        if (!empty($origin)) {
            // Get allowed domains from database same as CorsFilter
            $db = db_connect();
            $buttonsQuery = $db->query("SELECT DISTINCT domain, tenant_id FROM buttons WHERE status = 'active'");
            $allowedDomains = [];
            $tenantIdsForDomainLookup = [];

            if ($buttonsQuery) {
                $buttonRows = $buttonsQuery->getResultArray();
                foreach ($buttonRows as $row) {
                    if (!empty($row['domain'])) {
                        // If domain is __tenant__, resolve from domains table
                        if ($row['domain'] === '__tenant__') {
                            $tenantIdsForDomainLookup[] = $row['tenant_id'];
                            continue;
                        }
                        // Handle comma-separated domains
                        $domains = explode(',', $row['domain']);
                        foreach ($domains as $domain) {
                            $domain = rtrim(trim($domain), '/');
                            if (!empty($domain)) {
                                $allowedDomains[] = $domain;
                            }
                        }
                    }
                }
            }

            // Resolve __tenant__ domains from the domains table
            if (!empty($tenantIdsForDomainLookup)) {
                $tenantIdsForDomainLookup = array_unique($tenantIdsForDomainLookup);
                $placeholders = implode(',', array_fill(0, count($tenantIdsForDomainLookup), '?'));
                $domainsQuery = $db->query(
                    "SELECT DISTINCT domain FROM domains WHERE tenant_id IN ({$placeholders})",
                    $tenantIdsForDomainLookup
                );

                if ($domainsQuery) {
                    foreach ($domainsQuery->getResultArray() as $row) {
                        $domain = rtrim(trim($row['domain']), '/');
                        if (!empty($domain)) {
                            $cleanDomain = preg_replace('#^https?://#', '', $domain);
                            $cleanDomain = rtrim($cleanDomain, '/');
                            $allowedDomains[] = 'https://' . $cleanDomain;
                            $allowedDomains[] = 'http://' . $cleanDomain;
                            $allowedDomains[] = $cleanDomain;
                        }
                    }
                }
            }

            // Check if origin is allowed (exact match or host-only match)
            $originAllowed = false;
            $originHost = parse_url($origin, PHP_URL_HOST);

            foreach ($allowedDomains as $allowed) {
                if ($origin === $allowed) {
                    $originAllowed = true;
                    break;
                }
                // Host-only fallback
                $allowedHost = parse_url($allowed, PHP_URL_HOST) ?: $allowed;
                if ($originHost === $allowedHost) {
                    $originAllowed = true;
                    break;
                }
            }

            if ($originAllowed || $this->allowed_origins === '*') {
                header("Access-Control-Allow-Origin: {$origin}");
                header('Access-Control-Allow-Credentials: true');
                log_debug('PROXY', 'CORS headers set for streaming', [
                    'origin' => $origin,
                    'allowed' => true
                ]);
            }
        } else {
            // Fallback to wildcard if no origin (development)
            if ($this->allowed_origins === '*') {
                header('Access-Control-Allow-Origin: *');
            }
        }

        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
        header('Access-Control-Max-Age: 86400');
    }

    /**
     * Check if user has available quota for requests
     */
    private function _check_user_quota($api_user, $tenant_id)
    {
        $db = db_connect();
        
        // Get current month usage
        $firstDayOfMonth = date('Y-m-01 00:00:00');
        $monthlyUsage = $db->table('usage_logs')
            ->selectSum('tokens')
            ->where('tenant_id', $tenant_id)
            ->where('external_id', $api_user['external_id'])
            ->where('created_at >=', $firstDayOfMonth)
            ->get()
            ->getRowArray();
        
        $currentMonthlyUsage = (int)($monthlyUsage['tokens'] ?? 0);
        $monthlyQuota = (int)($api_user['quota'] ?? 10000);
        
        // Get current day usage
        $today = date('Y-m-d 00:00:00');
        $dailyUsage = $db->table('usage_logs')
            ->selectSum('tokens')
            ->where('tenant_id', $tenant_id)
            ->where('external_id', $api_user['external_id'])
            ->where('created_at >=', $today)
            ->get()
            ->getRowArray();
        
        $currentDailyUsage = (int)($dailyUsage['tokens'] ?? 0);
        $dailyQuota = (int)($api_user['daily_quota'] ?? 10000);
        
        // Check monthly quota
        if ($currentMonthlyUsage >= $monthlyQuota) {
            return [
                'allowed' => false,
                'message' => "Monthly quota exceeded. Used: {$currentMonthlyUsage}/{$monthlyQuota} tokens. Quota resets on " . date('Y-m-01', strtotime('+1 month')),
                'quota_type' => 'monthly',
                'used' => $currentMonthlyUsage,
                'limit' => $monthlyQuota
            ];
        }
        
        // Check daily quota
        if ($currentDailyUsage >= $dailyQuota) {
            return [
                'allowed' => false,
                'message' => "Daily quota exceeded. Used: {$currentDailyUsage}/{$dailyQuota} tokens. Quota resets tomorrow.",
                'quota_type' => 'daily',
                'used' => $currentDailyUsage,
                'limit' => $dailyQuota
            ];
        }
        
        return [
            'allowed' => true,
            'monthly_remaining' => $monthlyQuota - $currentMonthlyUsage,
            'daily_remaining' => $dailyQuota - $currentDailyUsage,
            'monthly_used' => $currentMonthlyUsage,
            'daily_used' => $currentDailyUsage
        ];
    }
}
