<?php

namespace App\Libraries\LlmProviders;

class OpenAiProvider extends BaseLlmProvider
{
    /**
     * Process a request through OpenAI's API
     */
    public function process_request(string $model, array $messages, array $options = []): array
    {
        try {
            $data = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? 2000,
                'frequency_penalty' => $options['frequency_penalty'] ?? 0,
                'presence_penalty' => $options['presence_penalty'] ?? 0,
                'stream' => false
            ];

            $response = $this->make_request(
                $this->endpoint . '/chat/completions',
                $data
            );

            return [
                'response' => $response['choices'][0]['message']['content'],
                'tokens_in' => $response['usage']['prompt_tokens'] ?? 0,
                'tokens_out' => $response['usage']['completion_tokens'] ?? 0
            ];
        } catch (\Exception $e) {
            log_error('OPENAI', 'Error processing request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Process a streaming request through OpenAI's API
     */
    public function process_stream_request(string $model, array $messages, array $options = []): callable
    {
        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 2000,
            'frequency_penalty' => $options['frequency_penalty'] ?? 0,
            'presence_penalty' => $options['presence_penalty'] ?? 0,
            'stream' => true
        ];

        return $this->make_request(
            $this->endpoint . '/chat/completions',
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
            $content = $message['content'];
            if (is_string($content)) {
                $total_chars += strlen($content);
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
