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
     * Initialize proxy configuration
     * 
     * Carga la configuración del proxy desde el archivo de configuración
     * y establece las variables necesarias
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
     * Process request through LLM provider
     */
    private function _process_request($tenant_id, $external_id, $provider, $model, $messages, $options = [])
    {
        $request_id = uniqid('llm_');
        $db = db_connect();

        try {
            // Get LLM provider instance
            $llm = $this->_get_llm_provider($provider);
            if (!$llm) {
                throw new \Exception("Invalid provider: $provider");
            }

            // Get button system prompt if available
            $button_id = $this->request->getPost('buttonId');
            $system_prompt = null;
            $system_prompt_source = null;

            if ($button_id) {
                $button = $db->table('buttons')
                    ->where('button_id', $button_id)
                    ->get()
                    ->getRow();
                if ($button && $button->system_prompt) {
                    $system_prompt = $button->system_prompt;
                    $system_prompt_source = 'button';
                }
            }

            // Check if there's a system message in the request
            foreach ($messages as $msg) {
                if ($msg['role'] === 'system') {
                    $system_prompt = $msg['content'];
                    $system_prompt_source = 'request';
                    break;
                }
            }

            // Process request through provider
            $response = $llm->process_request($model, $messages, $options);

            // Check quota and log usage
            $tokens_in = $response['usage']['prompt_tokens'] ?? 0;
            $tokens_out = $response['usage']['completion_tokens'] ?? 0;
            
            // Log usage and update quota
            $usage_result = $this->_check_and_update_quota($tenant_id, $external_id, $tokens_in, $tokens_out, $provider, $model);
            
            // Get the last inserted usage_log_id
            $usage_log_id = $db->insertID();

            // Log the prompt and response
            $prompt_log = [
                'usage_log_id' => $usage_log_id,
                'tenant_id' => $tenant_id,
                'button_id' => $button_id,
                'messages' => json_encode($messages),
                'system_prompt' => $system_prompt,
                'system_prompt_source' => $system_prompt_source,
                'response' => json_encode($response['choices'][0]['message'] ?? $response),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $db->table('prompt_logs')->insert($prompt_log);

            log_info('LLM', 'Request processed successfully', [
                'request_id' => $request_id,
                'tenant_id' => $tenant_id,
                'external_id' => $external_id,
                'provider' => $provider,
                'model' => $model,
                'system_prompt_source' => $system_prompt_source
            ]);

            return $response;

        } catch (\Exception $e) {
            log_error('LLM', 'Error processing request', [
                'request_id' => $request_id,
                'tenant_id' => $tenant_id,
                'external_id' => $external_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle stream chunk from LLM provider
     */
    private function _handle_stream_chunk($data, $tenant_id, $external_id, $provider, $model, $has_image)
    {
        $lines = explode("\n", $data);
        $response = '';

        // Get button system prompt if available
        $db = db_connect();
        $button_id = $this->request->getPost('buttonId');
        $system_prompt = null;
        $system_prompt_source = null;

        if ($button_id) {
            $button = $db->table('buttons')
                ->where('button_id', $button_id)
                ->get()
                ->getRow();
            if ($button && $button->system_prompt) {
                $system_prompt = $button->system_prompt;
                $system_prompt_source = 'button';
            }
        }

        // Check if there's a system message in the request
        $messages = $this->request->getPost('messages') ?? [];
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $system_prompt = $msg['content'];
                $system_prompt_source = 'request';
                break;
            }
        }

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            // Si la línea comienza con "data: "
            if (strpos($line, 'data: ') === 0) {
                $content = substr($line, 6); // Remover "data: "

                // Intentar decodificar el JSON
                $json = json_decode($content, true);
                if ($json === null) {
                    log_warning('STREAM_CHUNK', 'Error decodificando JSON', [
                        'content' => $content,
                        'json_error' => json_last_error_msg()
                    ]);

                    // Si hay un error con el formato JSON, aún así reenviar al cliente
                    echo "data: " . $content . "\n\n";
                    flush();
                    continue;
                }

                // Acumular el contenido para el log
                if (isset($json['choices'][0]['delta']['content'])) {
                    $response .= $json['choices'][0]['delta']['content'];
                }

                // Reenviar el chunk al cliente
                echo "data: " . $content . "\n\n";
                flush();

            } else {
                // Si el proveedor no devuelve formato "data: ", intentamos adaptarlo
                $json = json_decode($line, true);
                if ($json !== null) {
                    echo "data: " . $line . "\n\n";
                    flush();
                    
                    // Acumular el contenido para el log
                    if (isset($json['choices'][0]['delta']['content'])) {
                        $response .= $json['choices'][0]['delta']['content'];
                    }
                } else {
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
                    
                    // Acumular el contenido para el log
                    $response .= $line;
                }
            }
        }

        // Al final del streaming, guardar el log
        if (!empty($response)) {
            // Log usage and get ID
            $usage_result = $this->_check_and_update_quota($tenant_id, $external_id, 0, 0, $provider, $model);
            $usage_log_id = $db->insertID();

            // Log the prompt and response
            $prompt_log = [
                'usage_log_id' => $usage_log_id,
                'tenant_id' => $tenant_id,
                'button_id' => $button_id,
                'messages' => json_encode($messages),
                'system_prompt' => $system_prompt,
                'system_prompt_source' => $system_prompt_source,
                'response' => json_encode(['content' => $response]),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $db->table('prompt_logs')->insert($prompt_log);

            log_info('STREAM', 'Stream completed and logged', [
                'tenant_id' => $tenant_id,
                'external_id' => $external_id,
                'system_prompt_source' => $system_prompt_source
            ]);
        }

        return strlen($data);
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
     * Check and update quota for tenant
     */
    private function _check_and_update_quota($tenant_id, $external_id, $tokens_in, $tokens_out, $provider, $model)
    {
        // Get tenant's current quota usage
        $quota = $this->llm_proxy_model->get_tenant_quota($tenant_id);
        
        // Check if tenant has exceeded their quota
        if ($quota['tokens_used'] + $tokens_in + $tokens_out > $quota['tokens_limit']) {
            throw new \Exception('Quota exceeded for tenant');
        }

        // Update quota usage
        $this->llm_proxy_model->update_tenant_quota($tenant_id, $tokens_in + $tokens_out);
    }

    /**
     * Process LLM request
     */
    public function process()
    {
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

            // Validate required parameters
            if (!$model || empty($messages)) {
                throw new \Exception('Missing required parameters');
            }

            // Get LLM provider instance
            $llm = $this->_get_llm_provider($provider);

            // Process request through provider
            $response = $llm->process_request($model, $messages, $options);

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
