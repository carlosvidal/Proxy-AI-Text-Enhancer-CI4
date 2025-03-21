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
        $prompt = $this->_convert_messages_to_prompt($messages);

        $data = [
            'model' => $model,
            'prompt' => $prompt,
            'max_tokens_to_sample' => $options['max_tokens'] ?? 2000,
            'temperature' => $options['temperature'] ?? 0.7,
            'stream' => false
        ];

        $response = $this->make_request(
            $this->endpoint . '/v1/complete',
            $data,
            ['anthropic-version: 2023-06-01']
        );

        return [
            'response' => $response['completion'],
            'tokens_in' => $this->get_token_usage($messages)['tokens_in'],
            'tokens_out' => ceil(strlen($response['completion']) / 4) // Rough estimate
        ];
    }

    /**
     * Process a streaming request through Anthropic's API
     */
    public function process_stream_request(string $model, array $messages, array $options = []): callable
    {
        // Convert messages array to Anthropic format
        $prompt = $this->_convert_messages_to_prompt($messages);

        $data = [
            'model' => $model,
            'prompt' => $prompt,
            'max_tokens_to_sample' => $options['max_tokens'] ?? 2000,
            'temperature' => $options['temperature'] ?? 0.7,
            'stream' => true
        ];

        return $this->make_request(
            $this->endpoint . '/v1/complete',
            $data,
            ['anthropic-version: 2023-06-01'],
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

    /**
     * Convert messages array to Anthropic prompt format
     */
    private function _convert_messages_to_prompt(array $messages): string
    {
        $prompt = '';
        foreach ($messages as $message) {
            $role = $message['role'];
            $content = $message['content'];

            if (is_array($content)) {
                // Handle multimodal messages
                $text_parts = [];
                foreach ($content as $part) {
                    if (is_string($part)) {
                        $text_parts[] = $part;
                    }
                }
                $content = implode("\n", $text_parts);
            }

            switch ($role) {
                case 'system':
                    $prompt .= "\n\nHuman: System instruction: {$content}\n\nAssistant: I understand.";
                    break;
                case 'user':
                    $prompt .= "\n\nHuman: {$content}";
                    break;
                case 'assistant':
                    $prompt .= "\n\nAssistant: {$content}";
                    break;
            }
        }

        // Add final Human: prefix if last message was from assistant
        if (end($messages)['role'] === 'assistant') {
            $prompt .= "\n\nHuman: ";
        }

        return trim($prompt);
    }
}
