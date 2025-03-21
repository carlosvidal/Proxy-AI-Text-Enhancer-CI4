<?php

namespace App\Libraries\LlmProviders;

interface LlmProviderInterface
{
    /**
     * Process a request through the LLM provider
     */
    public function process_request(string $model, array $messages, array $options = []): array;

    /**
     * Get token usage for a request
     */
    public function get_token_usage(array $messages): array;
}
