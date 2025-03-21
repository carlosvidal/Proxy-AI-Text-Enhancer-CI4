<?php

namespace App\Libraries\LlmProviders;

class OpenAiProvider extends BaseLlmProvider
{
    /**
     * Process a request through OpenAI
     */
    public function process_request(string $model, array $messages, array $options = []): array
    {
        $temperature = $options['temperature'] ?? 0.7;
        $stream = $options['stream'] ?? false;

        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => floatval($temperature),
            'stream' => $stream,
            'max_tokens' => 2000,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ];

        $response = $this->make_request($this->endpoint, $data);

        if (isset($response['error'])) {
            log_error('OPENAI', 'Error en respuesta de OpenAI', [
                'error' => $response['error']
            ]);
            throw new \Exception($response['error']['message'] ?? 'Unknown error from OpenAI');
        }

        // Asegurarse de que la respuesta tiene la estructura esperada
        if (!isset($response['choices']) || !isset($response['choices'][0]['message'])) {
            log_error('OPENAI', 'Respuesta inesperada de OpenAI', [
                'response' => $response
            ]);
            throw new \Exception('Unexpected response format from OpenAI');
        }

        // Devolver la respuesta en el formato esperado por el controlador
        return [
            'response' => $response['choices'][0]['message']['content'],
            'tokens_in' => $response['usage']['prompt_tokens'] ?? 0,
            'tokens_out' => $response['usage']['completion_tokens'] ?? 0,
            'raw_response' => $response // Mantener la respuesta completa para referencia
        ];
    }

    /**
     * Process a streaming request through OpenAI
     */
    public function process_stream_request(string $model, array $messages, array $options = []): callable
    {
        $temperature = $options['temperature'] ?? 0.7;

        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => floatval($temperature),
            'stream' => true,
            'max_tokens' => 2000,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ];

        return $this->make_request($this->endpoint, $data, [], true);
    }

    /**
     * Get token usage for a request
     */
    public function get_token_usage(array $messages): array
    {
        // Implementación básica - OpenAI proporciona el conteo en la respuesta
        return [
            'tokens_in' => 0,
            'tokens_out' => 0
        ];
    }
}
