<?php

namespace App\Libraries\LlmProviders;

class AnthropicProvider extends BaseLlmProvider
{
    /**
     * Process a request through Anthropic's API
     */
    public function process_request(string $model, array $messages, array $options = []): array
    {
        // Convert messages array to Anthropic format
        $prompt = '';
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $prompt .= $message['content'] . "\n\n";
            } else {
                $role = $message['role'] === 'assistant' ? 'Assistant' : 'Human';
                $prompt .= $role . ': ' . $message['content'] . "\n\n";
            }
        }
        $prompt .= 'Assistant: ';

        $data = [
            'model' => $model,
            'prompt' => $prompt,
            'max_tokens_to_sample' => $options['max_tokens'] ?? 2000,
            'temperature' => $options['temperature'] ?? 0.7,
            'stream' => $options['stream'] ?? false
        ];

        $response = $this->make_request(
            $this->endpoint . '/v1/complete',
            $data,
            ['anthropic-version: 2023-06-01']
        );

        return [
            'response' => $response['completion'],
            'tokens_in' => $response['usage']['prompt_tokens'] ?? $this->get_token_usage($messages)['tokens_in'],
            'tokens_out' => $response['usage']['completion_tokens'] ?? strlen($response['completion']) / 4
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
