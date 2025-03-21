<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Proxy extends BaseConfig
{
    /**
     * API Keys for different LLM providers
     */
    public $openaiApiKey = '';
    public $anthropicApiKey = '';
    public $googleApiKey = '';
    public $azureApiKey = '';

    /**
     * API Endpoints for different LLM providers
     */
    public $openaiEndpoint = 'https://api.openai.com/v1';
    public $anthropicEndpoint = 'https://api.anthropic.com';
    public $googleEndpoint = 'https://generativelanguage.googleapis.com/v1';
    public $azureEndpoint = '';

    /**
     * Other configuration options
     */
    public $useSimulatedResponses = false;
    public $allowedOrigins = '*';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Override configuration with environment variables if they exist
        $this->openaiApiKey = getenv('OPENAI_API_KEY') ?: $this->openaiApiKey;
        $this->anthropicApiKey = getenv('ANTHROPIC_API_KEY') ?: $this->anthropicApiKey;
        $this->googleApiKey = getenv('GOOGLE_API_KEY') ?: $this->googleApiKey;
        $this->azureApiKey = getenv('AZURE_API_KEY') ?: $this->azureApiKey;

        $this->openaiEndpoint = getenv('OPENAI_API_ENDPOINT') ?: $this->openaiEndpoint;
        $this->anthropicEndpoint = getenv('ANTHROPIC_API_ENDPOINT') ?: $this->anthropicEndpoint;
        $this->googleEndpoint = getenv('GOOGLE_API_ENDPOINT') ?: $this->googleEndpoint;
        $this->azureEndpoint = getenv('AZURE_API_ENDPOINT') ?: $this->azureEndpoint;

        $this->useSimulatedResponses = getenv('USE_SIMULATED_RESPONSES') === 'true' || $this->useSimulatedResponses;
        $this->allowedOrigins = getenv('ALLOWED_ORIGINS') ?: $this->allowedOrigins;
    }
}
