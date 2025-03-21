<?php

namespace App\Libraries\LlmProviders;

class OpenAiProvider extends BaseProvider
{
    private $api_key;
    private $api_url = 'https://api.openai.com/v1/chat/completions';

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    public function process_request($model, $messages, $options = [])
    {
        try {
            // Prepare request payload
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $options['temperature'] ?? 0.7,
                'stream' => false
            ];

            // Make API request
            $ch = curl_init($this->api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->api_key
            ]);

            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status !== 200) {
                throw new \Exception('OpenAI API error: ' . $response);
            }

            $response = json_decode($response, true);

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

    public function process_stream_request($model, $messages, $options = [])
    {
        return function() use ($model, $messages, $options) {
            try {
                // Prepare request payload
                $payload = [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => $options['temperature'] ?? 0.7,
                    'stream' => true
                ];

                // Initialize curl
                $ch = curl_init($this->api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->api_key,
                    'Accept: text/event-stream'
                ]);

                // Set callback for streaming
                curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
                    $lines = explode("\n", $data);
                    foreach ($lines as $line) {
                        if (strlen(trim($line)) === 0) continue;
                        if (strpos($line, 'data: ') !== 0) continue;

                        $line = substr($line, 6);
                        if ($line === '[DONE]') {
                            echo "data: [DONE]\n\n";
                            flush();
                            continue;
                        }

                        $response = json_decode($line, true);
                        if (!$response) continue;

                        if (isset($response['choices'][0]['delta']['content'])) {
                            $chunk = $response['choices'][0]['delta']['content'];
                            echo "data: " . $chunk . "\n\n";
                            flush();
                        }
                    }
                    return strlen($data);
                });

                // Execute request
                curl_exec($ch);
                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($status !== 200) {
                    throw new \Exception('OpenAI API error: HTTP ' . $status);
                }
            } catch (\Exception $e) {
                throw new \Exception('Error processing OpenAI stream: ' . $e->getMessage());
            }
        };
    }

    public function get_token_usage($messages)
    {
        // Estimate token usage based on message length
        $total_chars = 0;
        foreach ($messages as $message) {
            $total_chars += strlen($message['content']);
        }

        // Rough estimate: 4 characters per token
        $tokens = ceil($total_chars / 4);

        return [
            'tokens_in' => $tokens,
            'tokens_out' => 0  // Will be updated after completion
        ];
    }
}
