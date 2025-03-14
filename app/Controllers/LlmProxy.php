<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\LlmProxyModel;

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
        helper(['url', 'form', 'logger', 'jwt', 'api_key']);

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

        // Get request body and validate
        $json_data = $this->request->getBody();
        $request_data = json_decode($json_data, TRUE);

        if (!$request_data) {
            log_error('PROXY', 'Invalid request data', [
                'request_id' => $request_id,
                'raw_data' => $json_data
            ]);
            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(400)
                ->setJSON(['error' => ['message' => 'Invalid request data - failed to parse JSON']]);
        }

        // Log request parameters
        log_debug('PROXY', 'Request parameters', [
            'request_id' => $request_id,
            'has_messages' => isset($request_data['messages']),
            'provider' => $request_data['provider'] ?? 'not_set',
            'model' => $request_data['model'] ?? 'not_set',
            'stream' => isset($request_data['stream']) ? 'true' : 'false'
        ]);

        // Extract tenant and user information
        // Use JWT data for tenant/user if available, otherwise use from request
        $tenant_id = '';
        $user_id = '';

        if ($jwtData && isset($jwtData->tenant_id) && isset($jwtData->user_id)) {
            $tenant_id = $jwtData->tenant_id;
            $user_id = $jwtData->user_id;
            log_info('PROXY', 'Using JWT tenant/user data', [
                'request_id' => $request_id,
                'tenant_id' => $tenant_id,
                'user_id' => $user_id
            ]);
        } else {
            $tenant_id = isset($request_data['tenantId']) ? $request_data['tenantId'] : '';
            $user_id = isset($request_data['userId']) ? $request_data['userId'] : '';
        }

        // Get button_id if provided
        $button_id = isset($request_data['buttonId']) ? $request_data['buttonId'] : '';

        // Get origin domain
        $origin_domain = $this->_extract_domain_from_headers();

        // Get domain from request if provided
        $domain = isset($request_data['domain']) ? $request_data['domain'] : $origin_domain;

        // Log the button and domain information
        log_info('PROXY', 'Button and domain information', [
            'request_id' => $request_id,
            'button_id' => $button_id,
            'domain' => $domain,
            'origin_domain' => $origin_domain
        ]);

        $has_image = isset($request_data['hasImage']) ? $request_data['hasImage'] : FALSE;

        // If we don't have tenant_id or user_id, can't proceed
        if (empty($tenant_id)) {
            log_error('PROXY', 'Missing tenant ID', [
                'request_id' => $request_id
            ]);
            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(400)
                ->setJSON(['error' => ['message' => 'Missing tenant ID']]);
        }

        if (empty($user_id)) {
            log_error('PROXY', 'Missing user ID', [
                'request_id' => $request_id
            ]);
            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(400)
                ->setJSON(['error' => ['message' => 'Missing user ID']]);
        }

        // Look up button configuration - prioritize button_id if provided
        $button = null;
        $buttonsModel = new \App\Models\ButtonsModel();

        if (!empty($button_id)) {
            // Look up by button_id
            $button = $buttonsModel->getButtonByButtonId($button_id);

            if ($button) {
                log_info('PROXY', 'Found button configuration by ID', [
                    'request_id' => $request_id,
                    'button_id' => $button_id,
                    'provider' => $button['provider'],
                    'model' => $button['model']
                ]);

                // Verify domain matches if we have an origin
                if (!empty($origin_domain) && $button['domain'] !== $origin_domain) {
                    log_warning('PROXY', 'Domain mismatch for button', [
                        'request_id' => $request_id,
                        'button_domain' => $button['domain'],
                        'origin_domain' => $origin_domain
                    ]);

                    // Return error if domains don't match
                    return $this->response
                        ->setContentType('application/json')
                        ->setStatusCode(400)
                        ->setJSON(['error' => ['message' => 'Domain mismatch for the provided button ID']]);
                }
            } else {
                log_error('PROXY', 'Button not found with ID', [
                    'request_id' => $request_id,
                    'button_id' => $button_id
                ]);

                return $this->response
                    ->setContentType('application/json')
                    ->setStatusCode(400)
                    ->setJSON(['error' => ['message' => 'Button not found with the provided ID']]);
            }
        }
        // If no button_id provided but we have domain, try to find by domain
        else if (!empty($domain)) {
            $button = $buttonsModel->getButtonByDomain($domain, $tenant_id);

            if ($button) {
                log_info('PROXY', 'Found button configuration by domain', [
                    'request_id' => $request_id,
                    'domain' => $domain,
                    'provider' => $button['provider'],
                    'model' => $button['model']
                ]);
            } else {
                log_warning('PROXY', 'Button configuration not found for domain', [
                    'request_id' => $request_id,
                    'domain' => $domain,
                    'tenant_id' => $tenant_id
                ]);

                // Fall back to request data
                if (!isset($request_data['provider']) || !isset($request_data['model'])) {
                    log_error('PROXY', 'Missing provider or model and no button found', [
                        'request_id' => $request_id,
                        'request_data' => $request_data
                    ]);
                    return $this->response
                        ->setContentType('application/json')
                        ->setStatusCode(400)
                        ->setJSON(['error' => ['message' => 'No button found for this domain and no provider/model specified in request']]);
                }
            }
        }

        // Use provider/model from button, request, or default values
        $provider = '';
        $model = '';

        if ($button) {
            // Use button configuration
            $provider = $button['provider'];
            $model = $button['model'];

            // If button has a system prompt and there are messages, inject it
            if (!empty($button['system_prompt']) && isset($request_data['messages']) && is_array($request_data['messages'])) {
                // Check if there's already a system message
                $has_system = false;
                foreach ($request_data['messages'] as $msg) {
                    if (isset($msg['role']) && $msg['role'] === 'system') {
                        $has_system = true;
                        break;
                    }
                }

                // If no system message exists, add it as the first message
                if (!$has_system) {
                    array_unshift($request_data['messages'], [
                        'role' => 'system',
                        'content' => $button['system_prompt']
                    ]);
                }
            }

            // If button has its own API key, use it instead
            if (!empty($button['api_key'])) {
                $this->api_keys[$provider] = $button['api_key'];
                log_info('PROXY', 'Using button-specific API key', [
                    'request_id' => $request_id,
                    'button_id' => $button['button_id'],
                    'provider' => $provider
                ]);
            }
        } else {
            // Fall back to request data
            $provider = isset($request_data['provider']) ? $request_data['provider'] : '';
            $model = isset($request_data['model']) ? $request_data['model'] : '';
        }

        // If still no provider/model, can't proceed
        if (empty($provider)) {
            log_error('PROXY', 'Missing provider', [
                'request_id' => $request_id
            ]);
            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(400)
                ->setJSON(['error' => ['message' => 'Missing provider. Provide a button ID, domain or specify provider directly.']]);
        }

        if (empty($model)) {
            log_error('PROXY', 'Missing model', [
                'request_id' => $request_id
            ]);
            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(400)
                ->setJSON(['error' => ['message' => 'Missing model. Provide a button ID, domain or specify model directly.']]);
        }

        // Get remaining request parameters
        $messages = isset($request_data['messages']) ? $request_data['messages'] : [];
        $temperature = isset($request_data['temperature']) ? $request_data['temperature'] : 0.7;
        $stream = isset($request_data['stream']) ? $request_data['stream'] : TRUE;

        log_info('PROXY', 'Processing request with parameters', [
            'request_id' => $request_id,
            'provider' => $provider,
            'model' => $model,
            'tenant_id' => $tenant_id,
            'user_id' => $user_id,
            'domain' => $domain,
            'stream' => $stream ? 'true' : 'false'
        ]);

        // Auto-create user if it doesn't exist
        $this->_ensure_user_exists($tenant_id, $user_id);

        // API key verification
        if (!isset($this->api_keys[$provider]) || empty($this->api_keys[$provider])) {
            log_error('PROXY', 'Invalid or unsupported provider', [
                'request_id' => $request_id,
                'provider' => $provider,
                'api_key_exists' => isset($this->api_keys[$provider]),
                'api_key_empty' => isset($this->api_keys[$provider]) ? empty($this->api_keys[$provider]) : true
            ]);

            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(400)
                ->setJSON(['error' => ['message' => 'Invalid or unsupported provider']]);
        }

        log_info('PROXY', 'API key verified for ' . $provider, [
            'request_id' => $request_id
        ]);

        // Check quota
        $quota = $this->llm_proxy_model->check_quota($tenant_id, $user_id);
        log_info('PROXY', 'Quota check', [
            'request_id' => $request_id,
            'quota' => $quota
        ]);

        if ($quota && $quota['remaining'] <= 0) {
            log_error('PROXY', 'Quota exceeded', [
                'request_id' => $request_id,
                'tenant_id' => $tenant_id,
                'user_id' => $user_id,
                'quota' => $quota
            ]);

            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(403)
                ->setJSON(['error' => ['message' => 'Quota exceeded']]);
        }

        // Prepare payload based on provider
        $payload = $this->_prepare_payload($provider, $model, $messages, $temperature, $stream);
        log_debug('PROXY', 'Payload prepared for ' . $provider, [
            'request_id' => $request_id,
            'payload' => $payload
        ]);

        // Make request to LLM provider
        try {
            log_info('PROXY', 'Starting external API request', [
                'request_id' => $request_id,
                'provider' => $provider,
                'model' => $model
            ]);

            return $this->_make_request($provider, $payload, $stream, $tenant_id, $user_id, $model, $has_image);
        } catch (\Exception $e) {
            log_error('PROXY', 'Error processing request', [
                'request_id' => $request_id,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(500)
                ->setJSON(['error' => ['message' => 'Internal server error: ' . $e->getMessage()]]);
        }
    }

    /**
     * Ensures a user exists for a tenant, creating them if needed
     * 
     * @param string $tenant_id The tenant ID
     * @param string $user_id The user ID
     * @return bool True if user exists or was created, false on error
     */
    private function _ensure_user_exists($tenant_id, $user_id)
    {
        $db = db_connect();

        // Check if tenant exists
        $tenantsModel = new \App\Models\TenantsModel();
        $tenant = $tenantsModel->where('tenant_id', $tenant_id)->first();

        if (!$tenant) {
            log_error('PROXY', 'Tenant does not exist', [
                'tenant_id' => $tenant_id
            ]);
            return false;
        }

        // Check if user exists
        $builder = $db->table('tenant_users');
        $builder->where('tenant_id', $tenant_id);
        $builder->where('user_id', $user_id);
        $existingUser = $builder->get()->getRow();

        if (!$existingUser) {
            log_info('PROXY', 'Auto-creating user', [
                'tenant_id' => $tenant_id,
                'user_id' => $user_id
            ]);

            // Create the user in tenant_users
            $userData = [
                'tenant_id' => $tenant_id,
                'user_id' => $user_id,
                'name' => 'Auto-created User',
                'email' => $user_id . '@auto.created',
                'quota' => $tenant['quota'], // Use tenant's default quota
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $result = $db->table('tenant_users')->insert($userData);

            if (!$result) {
                log_error('PROXY', 'Failed to auto-create user', [
                    'tenant_id' => $tenant_id,
                    'user_id' => $user_id
                ]);
                return false;
            }

            // Also create in user_quotas
            $quotaData = [
                'tenant_id' => $tenant_id,
                'user_id' => $user_id,
                'total_quota' => $tenant['quota'], // Use tenant's default quota
                'reset_period' => 'monthly',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $db->table('user_quotas')->insert($quotaData);

            log_info('PROXY', 'User auto-created successfully', [
                'tenant_id' => $tenant_id,
                'user_id' => $user_id,
                'quota' => $tenant['quota']
            ]);
        }

        return true;
    }

    /**
     * Extrae el dominio del encabezado Origin o Referer
     * 
     * @return string Dominio extraído o cadena vacía si no se encuentra
     */
    private function _extract_domain_from_headers()
    {
        // Intentar obtener del encabezado Origin primero
        $origin = $this->request->getHeaderLine('Origin');

        if (!empty($origin)) {
            // Parsear la URL para obtener solo el dominio
            $parsedUrl = parse_url($origin);

            if (isset($parsedUrl['host'])) {
                log_info('PROXY', 'Extracted domain from Origin header', [
                    'origin' => $origin,
                    'domain' => $parsedUrl['host']
                ]);

                return $parsedUrl['host'];
            }
        }

        // Si no hay Origin, intentar con Referer
        $referer = $this->request->getHeaderLine('Referer');

        if (!empty($referer)) {
            // Parsear la URL para obtener solo el dominio
            $parsedUrl = parse_url($referer);

            if (isset($parsedUrl['host'])) {
                log_info('PROXY', 'Extracted domain from Referer header', [
                    'referer' => $referer,
                    'domain' => $parsedUrl['host']
                ]);

                return $parsedUrl['host'];
            }
        }

        log_warning('PROXY', 'No domain found in request headers');
        return '';
    }

    /**
     * Realiza la petición al proveedor LLM
     */
    private function _make_request($provider, $payload, $stream, $tenant_id, $user_id, $model, $has_image)
    {
        log_debug('API_REQUEST', 'Iniciando solicitud', [
            'provider' => $provider,
            'model' => $model,
            'stream' => $stream ? 'true' : 'false'
        ]);

        // Si estamos en desarrollo y queremos usar respuestas simuladas
        if (ENVIRONMENT == 'development' && $this->use_simulated_responses) {
            log_info('API_REQUEST', 'Usando respuesta simulada en entorno de desarrollo');

            // Lógica de simulación existente...
            if ($stream) {
                log_info('API_REQUEST', 'Generando respuesta en streaming');
                $this->_generate_ai_response($payload['messages'], true, $model);
            }

            $aiResponse = $this->_generate_ai_response($payload['messages'], false, $model);
            // La función _generate_ai_response se manejará según sea necesario
        }
        // Solicitud real al proveedor de LLM
        else {
            log_info('API_REQUEST', 'Realizando solicitud real al proveedor LLM', [
                'provider' => $provider,
                'model' => $model,
                'stream' => $stream ? 'true' : 'false'
            ]);

            $api_key = $this->api_keys[$provider];
            $endpoint = $this->endpoints[$provider];

            // Configurar opciones de cURL según el proveedor
            $headers = [
                "Content-Type: application/json"
            ];

            // Si el botón tiene un API key personalizado, usarlo (ya desencriptado por el modelo)
            // De lo contrario, usar el API key global
            $api_key = isset($button) && !empty($button['api_key'])
                ? $button['api_key']
                : $this->api_keys[$provider];

            // Agrega registros para depuración cuando uses un API key personalizado
            if (isset($button) && !empty($button['api_key'])) {
                log_info('PROXY', 'Usando API key personalizado del botón', [
                    'button_id' => $button['button_id'],
                    'provider' => $provider
                ]);
            }

            // Si un botón no tiene API key, asegúrate de que se use el global
            if (isset($button) && empty($button['api_key'])) {
                log_info('PROXY', 'Botón sin API key propio, usando global', [
                    'button_id' => $button['button_id'],
                    'provider' => $provider,
                    'global_key_available' => !empty($this->api_keys[$provider])
                ]);

                // Verificar si hay un API key global disponible
                if (empty($this->api_keys[$provider])) {
                    log_error('PROXY', 'No hay API key disponible para este proveedor', [
                        'provider' => $provider
                    ]);

                    return $this->response
                        ->setContentType('application/json')
                        ->setStatusCode(500)
                        ->setJSON(['error' => ['message' => 'API key not configured for this provider']]);
                }
            }


            switch ($provider) {
                case 'openai':
                    $headers[] = "Authorization: Bearer {$api_key}";
                    break;
                case 'anthropic':
                    $headers[] = "x-api-key: {$api_key}";
                    $headers[] = "anthropic-version: 2023-06-01";
                    break;
                case 'mistral':
                    $headers[] = "Authorization: Bearer {$api_key}";
                    break;
                    // Otros proveedores...
            }

            $curl = curl_init($endpoint);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($curl, CURLOPT_TIMEOUT, 120); // 2 minutos de timeout

            // Para streaming también necesitamos actualizar el manejo
            // Busca la sección donde se maneja el streaming y modifica:

            if ($stream) {
                // Registrar el inicio de streaming
                log_info('API_REQUEST', 'Iniciando streaming real con ' . $provider);

                // Configuración especial para streaming
                $token_count = 0; // Variable para rastrear tokens en streaming

                curl_setopt($curl, CURLOPT_WRITEFUNCTION, function ($curl, $data) use (&$token_count, $tenant_id, $user_id, $provider, $model, $has_image) {
                    // El resto del código actual para _handle_stream_chunk

                    // Intentar contar tokens aquí (esto es aproximado para streaming)
                    // Aproximadamente 4 caracteres = 1 token para inglés
                    // Esto es sólo una aproximación mientras llega el final del stream
                    $token_count += ceil(mb_strlen($data) / 4);

                    return $this->_handle_stream_chunk($data, $tenant_id, $user_id, $provider, $model, $has_image);
                });

                curl_setopt($curl, CURLOPT_VERBOSE, true);
                $verbose = fopen('php://temp', 'w+');
                curl_setopt($curl, CURLOPT_STDERR, $verbose);

                // Habilitar cabeceras para streaming
                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache');
                header('Connection: keep-alive');
                header('X-Accel-Buffering: no');

                // Ejecutar la solicitud
                $result = curl_exec($curl);

                rewind($verbose);
                $verboseLog = stream_get_contents($verbose);
                log_debug('CURL', 'Detalles de la solicitud cURL', [
                    'verbose_log' => $verboseLog
                ]);

                if (curl_errno($curl)) {
                    log_error('API_REQUEST', 'Error en solicitud streaming', [
                        'error' => curl_error($curl),
                        'code' => curl_errno($curl)
                    ]);

                    // Enviar error al cliente como evento SSE
                    $error_data = [
                        'error' => [
                            'message' => 'Error connecting to LLM provider: ' . curl_error($curl)
                        ]
                    ];
                    echo "data: " . json_encode($error_data) . "\n\n";
                    echo "data: [DONE]\n\n";
                    flush();
                }

                // Cerrar la conexión cURL
                curl_close($curl);

                // Registrar el uso con los tokens contados (aproximados para streaming)
                // Al menos tendremos una aproximación mejor que un número fijo
                $estimated_tokens = max($token_count, 50); // Mínimo 50 tokens para evitar valores demasiado bajos
                log_info('API_REQUEST', 'Streaming finalizado, tokens estimados', [
                    'provider' => $provider,
                    'model' => $model,
                    'estimated_tokens' => $estimated_tokens
                ]);

                $this->llm_proxy_model->record_usage($tenant_id, $user_id, $provider, $model, $has_image, $estimated_tokens);

                exit; // Terminar la ejecución después del streaming
            }
            // Para solicitudes sin streaming
            else {
                // Ejecutar la solicitud
                $response = curl_exec($curl);
                $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                if (curl_errno($curl)) {
                    log_error('API_REQUEST', 'Error en solicitud', [
                        'error' => curl_error($curl),
                        'code' => curl_errno($curl)
                    ]);

                    throw new \Exception('Error connecting to LLM provider: ' . curl_error($curl));
                }

                // Cerrar la conexión cURL
                curl_close($curl);

                // Verificar el código de estado
                if ($status != 200) {
                    log_error('API_REQUEST', 'Error en respuesta de API', [
                        'status' => $status,
                        'response' => $response
                    ]);

                    throw new \Exception('Error from LLM provider: ' . $response);
                }

                // Procesar la respuesta según el proveedor
                $response_data = json_decode($response, true);

                // Verificar si hay errores en la respuesta
                if (isset($response_data['error'])) {
                    log_error('API_REQUEST', 'Error reportado por el proveedor', [
                        'error' => $response_data['error']
                    ]);

                    throw new \Exception('Provider reported error: ' . $response_data['error']['message']);
                }

                // Extraer el contenido de la respuesta según el proveedor
                $content = '';

                // Extraer información de uso de tokens
                $prompt_tokens = 0;
                $completion_tokens = 0;
                $total_tokens = 0;

                switch ($provider) {
                    case 'openai':
                        $content = $response_data['choices'][0]['message']['content'];

                        // Extraer tokens de OpenAI
                        if (isset($response_data['usage'])) {
                            $prompt_tokens = $response_data['usage']['prompt_tokens'] ?? 0;
                            $completion_tokens = $response_data['usage']['completion_tokens'] ?? 0;
                            $total_tokens = $response_data['usage']['total_tokens'] ?? 0;
                        }
                        break;

                    case 'anthropic':
                        $content = $response_data['content'][0]['text'];

                        // Extraer tokens de Anthropic
                        if (isset($response_data['usage'])) {
                            $prompt_tokens = $response_data['usage']['input_tokens'] ?? 0;
                            $completion_tokens = $response_data['usage']['output_tokens'] ?? 0;
                            $total_tokens = $prompt_tokens + $completion_tokens;
                        }
                        break;

                    case 'mistral':
                        $content = $response_data['choices'][0]['message']['content'];

                        // Extraer tokens de Mistral
                        if (isset($response_data['usage'])) {
                            $prompt_tokens = $response_data['usage']['prompt_tokens'] ?? 0;
                            $completion_tokens = $response_data['usage']['completion_tokens'] ?? 0;
                            $total_tokens = $response_data['usage']['total_tokens'] ?? 0;
                        }
                        break;

                    // Otros proveedores...
                    default:
                        if (isset($response_data['choices'][0]['message']['content'])) {
                            $content = $response_data['choices'][0]['message']['content'];
                        } else {
                            log_error('API_REQUEST', 'Formato de respuesta no reconocido', [
                                'response' => $response_data
                            ]);
                            throw new \Exception('Unknown response format from provider');
                        }
                }

                // Registrar el uso con los tokens reales
                if ($total_tokens > 0) {
                    log_info('API_REQUEST', 'Tokens registrados', [
                        'prompt_tokens' => $prompt_tokens,
                        'completion_tokens' => $completion_tokens,
                        'total_tokens' => $total_tokens
                    ]);

                    $this->llm_proxy_model->record_usage($tenant_id, $user_id, $provider, $model, $has_image, $total_tokens);
                } else {
                    // Si no se pudo obtener información de tokens, usar estimación como respaldo
                    $this->llm_proxy_model->record_usage($tenant_id, $user_id, $provider, $model, $has_image);
                }

                // Estructurar la respuesta similar a OpenAI para consistencia
                $responseData = [
                    'id' => isset($response_data['id']) ? $response_data['id'] : 'resp-' . uniqid(),
                    'object' => 'chat.completion',
                    'created' => time(),
                    'model' => $model,
                    'choices' => [
                        [
                            'index' => 0,
                            'message' => [
                                'role' => 'assistant',
                                'content' => $content
                            ],
                            'finish_reason' => 'stop'
                        ]
                    ],
                    'usage' => [
                        'prompt_tokens' => $prompt_tokens,
                        'completion_tokens' => $completion_tokens,
                        'total_tokens' => $total_tokens
                    ]
                ];

                log_info('API_RESPONSE', 'Enviando respuesta al cliente (no streaming)');

                return $this->response
                    ->setContentType('application/json')
                    ->setJSON($responseData);
            }
        }
    }

    /**
     * Procesa y reenvía cada fragmento de datos recibidos en streaming
     */
    private function _handle_stream_chunk($data, $tenant_id, $user_id, $provider, $model, $has_image)
    {
        static $buffer = '';

        log_debug('STREAM_RAW', 'Datos brutos recibidos', [
            'length' => strlen($data),
            'preview' => strlen($data) > 100 ? substr($data, 0, 100) . '...' : $data
        ]);

        $buffer .= $data;
        $lines = explode("\n", $buffer);
        $buffer = array_pop($lines);  // Mantener cualquier parte incompleta para el próximo chunk

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            log_debug('STREAM_LINE', 'Procesando línea', [
                'line' => $line
            ]);

            if (strpos($line, 'data: ') === 0) {
                $content = substr($line, 6);

                // Si es [DONE], pasar tal cual
                if ($content === '[DONE]') {
                    log_info('STREAM_CHUNK', 'Recibido fin de stream [DONE]');
                    echo "data: [DONE]\n\n";
                    flush();
                    continue;
                }

                // Intentar interpretar como JSON
                $json = json_decode($content, true);
                if ($json === null) {
                    log_error('STREAM_CHUNK', 'Error decodificando JSON', [
                        'content' => $content,
                        'json_error' => json_last_error_msg()
                    ]);

                    // Si hay un error con el formato JSON, aún así reenviar al cliente
                    // para no interrumpir el flujo de datos
                    echo "data: " . $content . "\n\n";
                    flush();
                    continue;
                }

                // Logear el chunk para depuración
                log_debug('STREAM_CHUNK', 'Chunk JSON recibido del proveedor', [
                    'size' => strlen($content),
                    'content' => $content,
                    'delta' => isset($json['choices'][0]['delta']) ? $json['choices'][0]['delta'] : 'no-delta'
                ]);

                // Simplemente reenviar el chunk al cliente
                echo "data: " . $content . "\n\n";
                flush();
            } else {
                // Si el proveedor no devuelve formato "data: ", intentamos adaptarlo
                log_warning('STREAM_CHUNK', 'Formato no esperado, adaptando', [
                    'line' => $line
                ]);

                // Intentar determinar si es JSON
                $json = json_decode($line, true);
                if ($json !== null) {
                    // Es json válido, lo enviamos en formato SSE
                    echo "data: " . $line . "\n\n";
                    flush();
                } else {
                    // No es JSON, lo enviamos como texto
                    $textChunk = [
                        'id' => 'chatcmpl-' . uniqid(),
                        'object' => 'chat.completion.chunk',
                        'created' => time(),
                        'model' => $model,
                        'choices' => [
                            [
                                'index' => 0,
                                'delta' => [
                                    'content' => $line
                                ],
                                'finish_reason' => null
                            ]
                        ]
                    ];
                    echo "data: " . json_encode($textChunk) . "\n\n";
                    flush();
                }
            }
        }

        return strlen($data);
    }

    /**
     * Checks and updates quota for a tenant/user
     */
    private function _check_and_update_quota($tenant_id, $user_id, $tokens_in = 0, $tokens_out = 0, $provider = '', $model = '')
    {
        $request_id = uniqid('quota_update_');
        log_info('QUOTA', 'Checking and updating quota', [
            'request_id' => $request_id,
            'tenant_id' => $tenant_id,
            'user_id' => $user_id,
            'tokens_in' => $tokens_in,
            'tokens_out' => $tokens_out
        ]);

        try {
            // Get current quota
            $quota = $this->llm_proxy_model->check_quota($tenant_id, $user_id);
            
            log_debug('QUOTA', 'Current quota status', [
                'request_id' => $request_id,
                'quota_limit' => $quota['limit'] ?? 0,
                'quota_used' => $quota['used'] ?? 0,
                'quota_remaining' => $quota['remaining'] ?? 0,
                'reset_date' => $quota['reset_date'] ?? 'not set'
            ]);

            // Calculate token cost
            $total_tokens = $tokens_in + $tokens_out;
            if ($quota['remaining'] < $total_tokens) {
                log_warning('QUOTA', 'Quota exceeded', [
                    'request_id' => $request_id,
                    'tenant_id' => $tenant_id,
                    'user_id' => $user_id,
                    'tokens_requested' => $total_tokens,
                    'tokens_remaining' => $quota['remaining']
                ]);
                throw new \Exception('Quota exceeded');
            }

            // Update quota usage
            $this->llm_proxy_model->update_quota_usage($tenant_id, $user_id, $total_tokens);

            // Log usage
            $cost = $this->_calculate_request_cost($provider, $model, $tokens_in, $tokens_out);
            $this->llm_proxy_model->log_usage($tenant_id, $user_id, [
                'provider' => $provider,
                'model' => $model,
                'tokens_in' => $tokens_in,
                'tokens_out' => $tokens_out,
                'cost' => $cost
            ]);

            log_info('QUOTA', 'Usage logged successfully', [
                'request_id' => $request_id,
                'tenant_id' => $tenant_id,
                'user_id' => $user_id,
                'provider' => $provider,
                'model' => $model,
                'total_tokens' => $total_tokens,
                'cost' => $cost
            ]);

            return [
                'success' => true,
                'tokens_used' => $total_tokens,
                'tokens_remaining' => $quota['remaining'] - $total_tokens
            ];

        } catch (\Exception $e) {
            log_error('QUOTA', 'Error updating quota', [
                'request_id' => $request_id,
                'tenant_id' => $tenant_id,
                'user_id' => $user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Calculate request cost based on provider and model
     */
    private function _calculate_request_cost($provider, $model, $tokens_in, $tokens_out)
    {
        $request_id = uniqid('cost_calc_');
        
        // Default rates per 1K tokens (in USD)
        $rates = [
            'openai' => [
                'gpt-4' => ['input' => 0.03, 'output' => 0.06],
                'gpt-4-32k' => ['input' => 0.06, 'output' => 0.12],
                'gpt-3.5-turbo' => ['input' => 0.0015, 'output' => 0.002],
                'gpt-3.5-turbo-16k' => ['input' => 0.003, 'output' => 0.004]
            ],
            'anthropic' => [
                'claude-2' => ['input' => 0.008, 'output' => 0.024],
                'claude-instant-1' => ['input' => 0.0008, 'output' => 0.0024]
            ]
        ];

        log_debug('COST', 'Calculating request cost', [
            'request_id' => $request_id,
            'provider' => $provider,
            'model' => $model,
            'tokens_in' => $tokens_in,
            'tokens_out' => $tokens_out
        ]);

        // Get rates for provider/model
        $model_rates = $rates[$provider][$model] ?? ['input' => 0, 'output' => 0];
        
        // Calculate cost
        $input_cost = ($tokens_in / 1000) * $model_rates['input'];
        $output_cost = ($tokens_out / 1000) * $model_rates['output'];
        $total_cost = $input_cost + $output_cost;

        log_debug('COST', 'Cost calculation completed', [
            'request_id' => $request_id,
            'input_cost' => $input_cost,
            'output_cost' => $output_cost,
            'total_cost' => $total_cost
        ]);

        return $total_cost;
    }

    /**
     * Get quota status
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

        // Obtener parámetros
        $tenant_id = $this->request->getGet('tenantId');
        $user_id = $this->request->getGet('userId');

        if (empty($tenant_id) || empty($user_id)) {
            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(400)
                ->setJSON(['error' => ['message' => 'Missing tenant or user ID']]);
        }

        // Get JWT data if available for secure endpoint
        $jwtData = null;
        $token = get_jwt_from_header();
        if ($token) {
            $tokenData = validate_jwt($token);
            if ($tokenData && isset($tokenData->data)) {
                $tenant_id = $tokenData->data->tenant_id;
                $user_id = $tokenData->data->user_id;
            }
        }

        // Get quota and usage data
        $quota = $this->llm_proxy_model->check_quota($tenant_id, $user_id);
        $usage = $this->llm_proxy_model->get_usage_stats($tenant_id, $user_id);

        // Generate ETag based on quota and usage data
        $etagData = [
            'tenant_id' => $tenant_id,
            'user_id' => $user_id,
            'quota' => $quota,
            'usage' => [
                'total_requests' => $usage['total_requests'] ?? 0,
                'total_tokens' => $usage['total_tokens'] ?? 0
            ],
            'timestamp' => floor(time() / 60) * 60 // Round to nearest minute
        ];
        
        $etag = '"' . md5(json_encode($etagData)) . '"';
        
        // Check If-None-Match header
        $ifNoneMatch = $this->request->getHeaderLine('If-None-Match');
        if ($ifNoneMatch && $ifNoneMatch === $etag) {
            return $this->response
                ->setStatusCode(304)
                ->setHeader('ETag', $etag)
                ->setHeader('Cache-Control', 'private, must-revalidate, max-age=60')
                ->setHeader('Vary', 'Origin, Authorization');
        }

        // Prepare response with both quota and usage data
        $response = array_merge($quota, ['usage_stats' => $usage]);

        // Return response with cache headers
        return $this->response
            ->setContentType('application/json')
            ->setHeader('ETag', $etag)
            ->setHeader('Cache-Control', 'private, must-revalidate, max-age=60')
            ->setHeader('Vary', 'Origin, Authorization')
            ->setJSON($response);
    }
{{ ... }}
