<?php

namespace App\Libraries\LlmProviders;

class DeepseekProvider extends BaseLlmProvider
{
    /**
     * Process a request through Deepseek's API
     */
    public function process_request(string $model, array $messages, array $options = []): array
    {
        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 2000,
            'stream' => false
        ];

        $response = $this->make_request(
            $this->endpoint . '/v1/chat/completions',
            $data
        );

        return [
            'response' => $response['choices'][0]['message']['content'],
            'tokens_in' => $response['usage']['prompt_tokens'],
            'tokens_out' => $response['usage']['completion_tokens']
        ];
    }

    /**
     * Process a streaming request through Deepseek's API
     */
    public function process_stream_request(string $model, array $messages, array $options = []): callable
    {
        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 2000,
            'stream' => true
        ];

        return $this->make_request(
            $this->endpoint . '/v1/chat/completions',
            $data,
            [],
            true
        );
    }

    /**
     * Get token usage for a request
     */
    public function get_token_usage(array $messages): array
    {
        // For now, just return an estimate based on character count
        // In a real implementation, you would use a proper tokenizer
        $total_chars = 0;
        foreach ($messages as $message) {
            if (is_array($message['content'])) {
                // Handle multimodal messages
                foreach ($message['content'] as $content) {
                    if (is_string($content)) {
                        $total_chars += strlen($content);
                    }
                }
            } else {
                $total_chars += strlen($message['content']);
            }
        }

        // Rough estimate: 4 characters per token
        $tokens = ceil($total_chars / 4);

        return [
            'tokens_in' => $tokens,
            'tokens_out' => 0 // Will be updated after the actual response
        ];
    }
}
