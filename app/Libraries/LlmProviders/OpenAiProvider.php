<?php

namespace App\Libraries\LlmProviders;

class OpenAiProvider extends BaseLlmProvider
{
    public function __construct(string $api_key)
    {
        parent::__construct($api_key, 'https://api.openai.com/v1/chat/completions');
    }

    public function process_request(string $model, array $messages, array $options = []): array
    {
        try {
            // Prepare request payload
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $options['temperature'] ?? 0.7,
                'stream' => false
            ];

            // Make request using parent's make_request method
            $response = $this->make_request($this->endpoint, $payload);

            if (isset($response['error'])) {
                throw new \Exception($response['error']['message'] ?? 'Unknown error from OpenAI');
            }

            if (!isset($response['choices']) || !isset($response['choices'][0]['message'])) {
                throw new \Exception('Unexpected response format from OpenAI');
            }

            return [
                'response' => $response['choices'][0]['message']['content'],
                'tokens_in' => $response['usage']['prompt_tokens'] ?? 0,
                'tokens_out' => $response['usage']['completion_tokens'] ?? 0,
                'raw_response' => $response
            ];
        } catch (\Exception $e) {
            throw new \Exception('Error processing OpenAI request: ' . $e->getMessage());
        }
    }

    public function process_stream_request(string $model, array $messages, array $options = []): callable
    {
        // Prepare request payload
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'stream' => true
        ];

        // Make streaming request using parent's make_request method
        return $this->make_request($this->endpoint, $payload, [
            'Accept: text/event-stream'
        ], true);
    }

    public function get_token_usage(array $messages): array
    {
        // Convert messages to array if needed
        $messages_array = [];
        foreach ($messages as $message) {
            if (is_object($message)) {
                $message = (array)$message;
            }
            $messages_array[] = $message;
        }

        // Estimate token usage based on message length
        $total_chars = 0;
        foreach ($messages_array as $message) {
            if (isset($message['content'])) {
                $total_chars += strlen($message['content']);
            }
        }

        // Rough estimate: 4 characters per token
        $tokens = ceil($total_chars / 4);

        return [
            'tokens_in' => $tokens,
            'tokens_out' => 0  // Will be updated after completion
        ];
    }
}
