<?php

namespace App\Libraries\LlmProviders;

class MistralProvider extends BaseLlmProvider
{
    /**
     * Process a request through Mistral's API
     */
    public function process_request(string $model, array $messages, array $options = []): array
    {
        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 2000,
            'stream' => $options['stream'] ?? false
        ];

        $response = $this->make_request(
            $this->endpoint . '/v1/chat/completions',
            $data
        );

        return [
            'response' => $response['choices'][0]['message']['content'],
            'tokens_in' => $response['usage']['prompt_tokens'] ?? $this->get_token_usage($messages)['tokens_in'],
            'tokens_out' => $response['usage']['completion_tokens'] ?? strlen($response['choices'][0]['message']['content']) / 4
        ];
    }

    /**
     * Get token usage for a request
     */
    public function get_token_usage(array $messages): array
    {
        // For now, just return an estimate based on character count
        $total_chars = 0;
        foreach ($messages as $message) {
            $total_chars += strlen($message['content']);
        }

        // Rough estimate: 4 characters per token
        $tokens = ceil($total_chars / 4);

        return [
            'tokens_in' => $tokens,
            'tokens_out' => 0 // Will be updated after the actual response
        ];
    }
}
