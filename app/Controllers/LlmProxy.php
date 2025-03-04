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
        helper(['url', 'form', 'logger', 'jwt']);

        // Initialize proxy model
        $this->llm_proxy_model = new LlmProxyModel();

        // Initialize proxy configuration
        $this->_initialize_config();

        log_info('PROXY', 'Proxy initialized', [
            'ip' => service('request')->getIPAddress(),
            'method' => service('request')->getMethod(),
            'user_agent' => service('request')->getUserAgent()->getAgentString()
        ]);
    }

    /**
     * Main endpoint for proxy requests
     */
    public function index()
    {
        log_info('PROXY', 'Request received at main endpoint');

        // Verify this is a POST request
        if (service('request')->getMethod() !== 'post') {
            log_error('PROXY', 'Method not allowed', ['method' => service('request')->getMethod()]);

            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(405)
                ->setJSON(['error' => ['message' => 'Method not allowed']]);
        }

        // Get JWT data if available (when using JWT authentication)
        $jwtData = null;
        $token = get_jwt_from_header();
        if ($token) {
            $tokenData = validate_jwt($token);
            if ($tokenData && isset($tokenData->data)) {
                $jwtData = $tokenData->data;
                log_info('PROXY', 'JWT authenticated user', [
                    'username' => $jwtData->username ?? 'unknown',
                    'id' => $jwtData->id ?? 'unknown'
                ]);
            } else {
                log_warning('PROXY', 'Invalid JWT token provided');
            }
        } else {
            log_info('PROXY', 'No JWT token found, continuing with standard authentication');
        }

        // Get request body as JSON
        $json_data = $this->request->getBody();
        log_debug('PROXY', 'Received data (raw)', $json_data);

        $request_data = json_decode($json_data, TRUE);

        // Verify data is valid
        if (!$request_data || !isset($request_data['provider']) || !isset($request_data['model'])) {
            log_error('PROXY', 'Invalid request data', $request_data);

            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(400)
                ->setJSON(['error' => ['message' => 'Invalid request data']]);
        }

        log_info('PROXY', 'Valid request received', [
            'provider' => $request_data['provider'],
            'model' => $request_data['model'],
            'stream' => isset($request_data['stream']) ? $request_data['stream'] : 'Not specified',
            'has_image' => isset($request_data['hasImage']) ? $request_data['hasImage'] : 'Not specified'
        ]);

        // Extract request data
        $provider = $request_data['provider'];
        $model = $request_data['model'];
        $messages = isset($request_data['messages']) ? $request_data['messages'] : [];
        $temperature = isset($request_data['temperature']) ? $request_data['temperature'] : 0.7;
        $stream = isset($request_data['stream']) ? $request_data['stream'] : TRUE;

        // Use JWT data for tenant/user if available, otherwise use from request
        $tenant_id = '';
        $user_id = '';

        if ($jwtData && isset($jwtData->tenant_id) && isset($jwtData->user_id)) {
            $tenant_id = $jwtData->tenant_id;
            $user_id = $jwtData->user_id;
            log_info('PROXY', 'Using JWT tenant/user data', [
                'tenant_id' => $tenant_id,
                'user_id' => $user_id
            ]);
        } else {
            $tenant_id = isset($request_data['tenantId']) ? $request_data['tenantId'] : '';
            $user_id = isset($request_data['userId']) ? $request_data['userId'] : '';
        }

        $has_image = isset($request_data['hasImage']) ? $request_data['hasImage'] : FALSE;

        // API key verification
        if (!isset($this->api_keys[$provider]) || empty($this->api_keys[$provider])) {
            log_error('PROXY', 'Invalid or unsupported provider', [
                'provider' => $provider,
                'api_key_exists' => isset($this->api_keys[$provider]),
                'api_key_empty' => isset($this->api_keys[$provider]) ? empty($this->api_keys[$provider]) : true
            ]);

            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(400)
                ->setJSON(['error' => ['message' => 'Invalid or unsupported provider']]);
        }

        log_info('PROXY', 'API key verified for ' . $provider);

        // Check quota
        $quota = $this->llm_proxy_model->check_quota($tenant_id, $user_id);
        log_info('PROXY', 'Quota check', $quota);

        if ($quota && $quota['remaining'] <= 0) {
            log_error('PROXY', 'Quota exceeded', [
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
        log_debug('PROXY', 'Payload prepared for ' . $provider, $payload);

        // Make request to LLM provider
        try {
            log_info('PROXY', 'Starting external API request', [
                'provider' => $provider,
                'model' => $model
            ]);

            return $this->_make_request($provider, $payload, $stream, $tenant_id, $user_id, $model, $has_image);
        } catch (\Exception $e) {
            log_error('PROXY', 'Error processing request', [
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

            // Para streaming
            if ($stream) {
                // Registrar el inicio de streaming
                log_info('API_REQUEST', 'Iniciando streaming real con ' . $provider);

                // Configuración especial para streaming
                curl_setopt($curl, CURLOPT_WRITEFUNCTION, function ($curl, $data) use ($tenant_id, $user_id, $provider, $model, $has_image) {
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

                // Registrar el uso
                $this->llm_proxy_model->record_usage($tenant_id, $user_id, $provider, $model, $has_image);

                log_info('API_REQUEST', 'Streaming finalizado', [
                    'provider' => $provider,
                    'model' => $model
                ]);

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
                switch ($provider) {
                    case 'openai':
                        $content = $response_data['choices'][0]['message']['content'];
                        break;
                    case 'anthropic':
                        $content = $response_data['content'][0]['text'];
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

                // Registrar el uso
                $this->llm_proxy_model->record_usage($tenant_id, $user_id, $provider, $model, $has_image);

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
                    'usage' => isset($response_data['usage']) ? $response_data['usage'] : [
                        'prompt_tokens' => 0,
                        'completion_tokens' => 0,
                        'total_tokens' => 0
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
     * Endpoint para verificar cuota
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

        // Obtener cuota
        $quota = $this->llm_proxy_model->check_quota($tenant_id, $user_id);

        // Devolver resultado
        return $this->response
            ->setContentType('application/json')
            ->setJSON($quota);
    }

    /**
     * Devuelve información sobre el estado del proxy
     */
    public function status()
    {
        $db = db_connect();

        // Verificar si las API keys están configuradas
        $api_keys_status = [];
        foreach ($this->api_keys as $provider => $key) {
            $api_keys_status[$provider] = !empty($key);
        }

        // Verificar si las tablas están creadas
        $tables_status = [
            'user_quotas' => $db->tableExists('user_quotas'),
            'usage_logs' => $db->tableExists('usage_logs')
        ];

        // Obtener estadísticas de uso si las tablas existen
        $usage_stats = [];
        if ($tables_status['usage_logs']) {
            // Total de peticiones
            $total_requests = $db->table('usage_logs')->countAll();

            // Peticiones por proveedor
            $provider_stats = $db->table('usage_logs')
                ->select('provider, COUNT(*) as count')
                ->groupBy('provider')
                ->get()
                ->getResultArray();

            // Peticiones en las últimas 24 horas
            $recent_requests = $db->table('usage_logs')
                ->where('usage_date >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
                ->countAllResults();

            $usage_stats = [
                'total_requests' => $total_requests,
                'recent_requests' => $recent_requests,
                'by_provider' => array_column($provider_stats, 'count', 'provider')
            ];
        }

        // Información del sistema
        $system_info = [
            'php_version' => PHP_VERSION,
            'codeigniter_version' => \CodeIgniter\CodeIgniter::CI_VERSION,
            'server' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown',
            'database' => $db->getPlatform(),
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'post_max_size' => ini_get('post_max_size'),
            'environment' => ENVIRONMENT
        ];

        // Construir respuesta
        $response = [
            'status' => 'online',
            'api_keys' => $api_keys_status,
            'database' => [
                'connected' => $db->connID !== false,
                'tables' => $tables_status
            ],
            'usage' => $usage_stats,
            'system' => $system_info,
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Devolver información
        return $this->response
            ->setContentType('application/json')
            ->setJSON($response);
    }

    /**
     * Método OPTIONS para preflight requests
     */
    public function options()
    {
        // Cabeceras CORS para preflight requests
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 3600');
        header('Content-Type: text/plain');
        header('Content-Length: 0');
        header('HTTP/1.1 200 OK');
        return '';
    }

    /**
     * Endpoint para probar la conexión con APIs de LLM
     */
    public function test_connection()
    {
        // Solo permitir GET
        if (service('request')->getMethod() !== 'get') {
            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(405)
                ->setJSON(['error' => ['message' => 'Method not allowed']]);
        }

        // Obtener parámetros
        $provider = $this->request->getGet('provider') ?: 'openai';

        // Verificar que el proveedor es válido
        if (!isset($this->api_keys[$provider]) || empty($this->api_keys[$provider])) {
            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(400)
                ->setJSON(['error' => ['message' => 'Invalid or unsupported provider']]);
        }

        // Preparar solicitud simple
        $payload = [
            'model' => $this->request->getGet('model') ?: ($provider == 'openai' ? 'gpt-3.5-turbo' : ''),
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => 'Say hello world briefly.']
            ],
            'temperature' => 0.7,
            'stream' => false
        ];

        try {
            // Hacer solicitud real al proveedor
            $api_key = $this->api_keys[$provider];
            $endpoint = $this->endpoints[$provider];

            $headers = [
                "Content-Type: application/json",
            ];

            switch ($provider) {
                case 'openai':
                    $headers[] = "Authorization: Bearer {$api_key}";
                    break;
                case 'anthropic':
                    $headers[] = "x-api-key: {$api_key}";
                    $headers[] = "anthropic-version: 2023-06-01";
                    break;
                    // Otros proveedores...
            }

            $curl = curl_init($endpoint);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($curl);
            $info = curl_getinfo($curl);

            if (curl_errno($curl)) {
                throw new \Exception(curl_error($curl));
            }

            curl_close($curl);

            // Devolver resultado completo para diagnóstico
            $result = [
                'success' => true,
                'provider' => $provider,
                'http_code' => $info['http_code'],
                'total_time' => $info['total_time'],
                'raw_response' => $response,
                'parsed_response' => json_decode($response, true)
            ];

            return $this->response
                ->setContentType('application/json')
                ->setJSON($result);
        } catch (\Exception $e) {
            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
        }
    }

    /**
     * Inicializa la configuración del proxy
     */
    private function _initialize_config()
    {
        // API keys para diferentes proveedores desde la configuración
        $this->api_keys = [
            'openai' => env('OPENAI_API_KEY', ''),
            'anthropic' => env('ANTHROPIC_API_KEY', ''),
            'deepseek' => env('DEEPSEEK_API_KEY', ''),
            'cohere' => env('COHERE_API_KEY', ''),
            'google' => env('GOOGLE_API_KEY', ''),
            'mistral' => env('MISTRAL_API_KEY', '')
        ];

        // Verificar si hay configuración para simulación
        $this->use_simulated_responses = (bool)env('USE_SIMULATED_RESPONSES', false);

        // Depuración de API keys
        log_debug('CONFIG', 'API Keys cargadas', [
            'openai' => !empty($this->api_keys['openai']) ? 'configurada' : 'vacía',
            'anthropic' => !empty($this->api_keys['anthropic']) ? 'configurada' : 'vacía',
            'deepseek' => !empty($this->api_keys['deepseek']) ? 'configurada' : 'vacía',
            'cohere' => !empty($this->api_keys['cohere']) ? 'configurada' : 'vacía',
            'google' => !empty($this->api_keys['google']) ? 'configurada' : 'vacía',
            'mistral' => !empty($this->api_keys['mistral']) ? 'configurada' : 'vacía'
        ]);

        // Endpoints para diferentes proveedores
        $this->endpoints = [
            'openai' => "https://api.openai.com/v1/chat/completions",
            'deepseek' => "https://api.deepseek.com/v1/chat/completions",
            'anthropic' => "https://api.anthropic.com/v1/messages",
            'cohere' => "https://api.cohere.ai/v1/generate",
            'google' => "https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateText",
            'mistral' => "https://api.mistral.ai/v1/chat/completions"
        ];

        // Cargar también la configuración de CORS
        $allowed_origins_str = env('ALLOWED_ORIGINS', 'http://127.0.0.1:5500,http://localhost:5500');
        $this->allowed_origins = $allowed_origins_str === '*' ? '*' : explode(',', $allowed_origins_str);

        log_debug('CONFIG', 'Configuración inicializada', [
            'simulación' => $this->use_simulated_responses ? 'activa' : 'inactiva',
            'orígenes_permitidos' => is_array($this->allowed_origins) ? implode(', ', $this->allowed_origins) : $this->allowed_origins
        ]);
    }

    /**
     * Prepara el payload según el proveedor
     */
    private function _prepare_payload($provider, $model, $messages, $temperature, $stream)
    {
        // Payload base
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'stream' => $stream
        ];

        // Adaptaciones específicas por proveedor
        switch ($provider) {
            case 'anthropic':
                // Transformar el formato de mensajes para Anthropic
                $anthropic_messages = [];
                foreach ($messages as $msg) {
                    // Mapear roles de OpenAI a Anthropic
                    $role = $msg['role'];
                    if ($role === 'system') {
                        // Anthropic usa un campo system separado
                        $system_content = $msg['content'];
                        continue;
                    } else if ($role === 'assistant') {
                        $role = 'assistant';
                    } else {
                        $role = 'user';
                    }

                    $anthropic_messages[] = [
                        'role' => $role,
                        'content' => $msg['content']
                    ];
                }

                // Crear payload específico para Anthropic
                $payload = [
                    'model' => $model,
                    'messages' => $anthropic_messages,
                    'max_tokens' => 1000,
                    'temperature' => $temperature,
                    'stream' => $stream
                ];

                // Añadir system prompt si existe
                if (isset($system_content)) {
                    $payload['system'] = $system_content;
                }
                break;

            case 'cohere':
                // Preparar mensajes para Cohere
                $prompt = '';
                foreach ($messages as $msg) {
                    $role = $msg['role'];
                    $content = $msg['content'];

                    if ($role === 'system') {
                        $prompt .= "System: {$content}\n\n";
                    } else if ($role === 'assistant') {
                        $prompt .= "Assistant: {$content}\n\n";
                    } else {
                        $prompt .= "User: {$content}\n\n";
                    }
                }
                $prompt .= "Assistant:";

                $payload = [
                    'model' => $model,
                    'prompt' => $prompt,
                    'temperature' => $temperature,
                    'max_tokens' => 1000,
                    'stream' => $stream
                ];
                break;

            case 'google':
                // Transformar para el formato de Google Gemini
                $contents = [];
                foreach ($messages as $msg) {
                    $role = $msg['role'];
                    $content = $msg['content'];

                    if ($role === 'system') {
                        $role = 'user';
                        $content = "System instructions: {$content}";
                    }

                    $contents[] = [
                        'role' => $role === 'assistant' ? 'model' : 'user',
                        'parts' => [['text' => $content]]
                    ];
                }

                $payload = [
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => $temperature
                    ]
                ];
                break;
        }

        return $payload;
    }

    /**
     * Genera una respuesta simulada para desarrollo
     * 
     * @param array $messages Los mensajes del usuario
     * @param bool $stream Si se debe enviar como stream
     * @param string $model El modelo utilizado
     * @return string|void Devuelve texto si !$stream, o nada si envía streaming
     */
    private function _generate_ai_response($messages, $stream = false, $model = 'gpt-4-turbo')
    {
        // Obtener el último mensaje del usuario
        $lastMessage = end($messages);
        $userMessage = is_array($lastMessage['content'])
            ? 'Mensaje multimodal'
            : $lastMessage['content'];

        log_debug('AI_RESPONSE', 'Generando respuesta para', [
            'mensaje' => substr($userMessage, 0, 100) . (strlen($userMessage) > 100 ? '...' : ''),
            'stream' => $stream ? 'true' : 'false'
        ]);

        // Respuestas predefinidas para desarrollo
        $responses = [
            'describe un burro de planchar' =>
            'Un burro de planchar es un mueble plegable diseñado para facilitar el planchado de ropa. Generalmente, consiste en una superficie acolchada montada sobre una estructura metálica ajustable en altura. Cuenta con patas que le dan estabilidad y suele tener una cubierta de tela resistente al calor. Es una herramienta esencial en muchos hogares para mantener la ropa libre de arrugas.',

            'improve' => 'Este producto excepcional, fabricado con materiales de primera calidad, está disponible en diversas tallas para adaptarse a todas sus necesidades. Entre sus características destacan su excepcional durabilidad y su intuitiva facilidad de uso, ofreciendo una excelente relación calidad-precio que garantiza la satisfacción del cliente. Una inversión inteligente para quienes valoran tanto la calidad como la funcionalidad.',

            'default' => 'Gracias por su mensaje. Este producto premium está elaborado con materiales de alta calidad y viene en múltiples tamaños para satisfacer sus necesidades específicas. Sus características principales incluyen extraordinaria durabilidad y facilidad de uso intuitiva. Representa una excelente inversión, ofreciendo un valor excepcional por su precio.'
        ];

        // Elegir respuesta apropiada
        $aiResponse = '';
        $lowerMessage = strtolower(trim($userMessage));

        if (isset($responses[$lowerMessage])) {
            $aiResponse = $responses[$lowerMessage];
        } elseif (strpos($lowerMessage, 'improve') !== false) {
            $aiResponse = $responses['improve'];
        } else {
            $aiResponse = $responses['default'];
        }

        log_debug('AI_RESPONSE', 'Respuesta generada', [
            'longitud' => strlen($aiResponse),
            'primeros_chars' => substr($aiResponse, 0, 50) . '...'
        ]);

        // Si no es streaming, simplemente devolver la respuesta
        if (!$stream) {
            return $aiResponse;
        }

        // Para streaming, configurar cabeceras apropiadas
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Importante para Nginx

        log_info('STREAM', 'Iniciando streaming de respuesta', [
            'longitud_total' => strlen($aiResponse)
        ]);

        // Dividir la respuesta en fragmentos pequeños (simular streaming)
        $chunks = str_split($aiResponse, 10); // 10 caracteres por fragmento
        $totalChunks = count($chunks);

        // Primero enviar mensaje de inicio de respuesta (similar a OpenAI)
        $startJson = json_encode([
            'id' => 'chatcmpl-' . uniqid(),
            'object' => 'chat.completion.chunk',
            'created' => time(),
            'model' => $model,
            'choices' => [
                [
                    'index' => 0,
                    'delta' => [
                        'role' => 'assistant'
                    ],
                    'finish_reason' => null
                ]
            ]
        ]);
        echo "data: " . $startJson . "\n\n";
        flush();
        usleep(100000); // Pausa de 100ms

        // Enviar cada fragmento como un evento SSE
        foreach ($chunks as $index => $chunk) {
            $isLast = ($index == $totalChunks - 1);

            $data = [
                'id' => 'chatcmpl-' . uniqid(),
                'object' => 'chat.completion.chunk',
                'created' => time(),
                'model' => $model,
                'choices' => [
                    [
                        'index' => 0,
                        'delta' => [
                            'content' => $chunk
                        ],
                        'finish_reason' => $isLast ? 'stop' : null
                    ]
                ]
            ];

            // Enviar evento SSE
            echo "data: " . json_encode($data) . "\n\n";

            log_debug('STREAM', 'Chunk enviado', [
                'indice' => $index + 1,
                'de' => $totalChunks,
                'contenido' => $chunk
            ]);

            // Asegurarse que se envíe inmediatamente
            flush();

            // Pequeña pausa para simular generación progresiva
            usleep(100000); // 100ms
        }

        // Enviar evento final que indica fin de stream
        echo "data: [DONE]\n\n";
        log_info('STREAM', 'Streaming completado', [
            'chunks_enviados' => $totalChunks
        ]);
        flush();
        exit;
    }
}
