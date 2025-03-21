<?php

namespace App\Libraries\LlmProviders;

class GoogleProvider extends BaseLlmProvider
{
    /**
     * Process a request through Google's API
     */
    public function process_request(string $model, array $messages, array $options = []): array
    {
        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'maxOutputTokens' => $options['max_tokens'] ?? 2000,
            'stream' => false
        ];

        $response = $this->make_request(
            $this->endpoint . '/v1/models/' . $model . ':generateContent',
            $data,
            ['x-goog-api-key: ' . $this->api_key]
        );

        return [
            'response' => $response['candidates'][0]['content']['parts'][0]['text'],
            'tokens_in' => $response['usage']['promptTokenCount'],
            'tokens_out' => $response['usage']['candidatesTokenCount']
        ];
    }

    /**
     * Process a streaming request through Google's API
     */
    public function process_stream_request(string $model, array $messages, array $options = []): callable
    {
        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'maxOutputTokens' => $options['max_tokens'] ?? 2000,
            'stream' => true
        ];

        return $this->make_request(
            $this->endpoint . '/v1/models/' . $model . ':generateContent',
            $data,
            ['x-goog-api-key: ' . $this->api_key],
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
