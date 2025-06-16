<?php

namespace App\Libraries\LlmProviders;

abstract class BaseLlmProvider implements LlmProviderInterface
{
    protected $api_key;
    protected $endpoint;

    public function __construct(string $api_key, string $endpoint)
    {
        $this->api_key = $api_key;
        $this->endpoint = $endpoint;
    }

    /**
     * Make HTTP request to provider API
     * @return array|callable Array for non-streaming responses, callable for streaming
     */
    protected function make_request(string $url, array $data, array $headers = [], bool $stream = false)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => 'identity', // Disable compression
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->api_key,
                'Accept: text/event-stream',
                'Accept-Encoding: identity', // Disable compression
                'Cache-Control: no-cache',
                'Connection: keep-alive'
            ], $headers)
        ]);

        if ($stream) {
            // For streaming responses, return a callable that will yield chunks
            return function () use ($curl, $data) {
                try {
                    log_message('debug', '[STREAMING] Starting streaming request to provider');
                    
                    // Set up streaming with callback
                    curl_setopt($curl, CURLOPT_WRITEFUNCTION, function($ch, $data_chunk) use ($data) {
                        // Process each chunk as it comes in
                        $lines = explode("\n", $data_chunk);
                        
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (empty($line)) continue;

                            // Process SSE data
                            if (strpos($line, 'data: ') === 0) {
                                $line = substr($line, 6);
                                if ($line === '[DONE]') {
                                    echo "data: [DONE]\n\n";
                                    flush();
                                    ob_flush();
                                    log_message('debug', '[STREAMING] Sent [DONE] marker');
                                    continue;
                                }

                                $chunk = json_decode($line, true);
                                if ($chunk && isset($chunk['choices'][0]['delta']['content'])) {
                                    $content = $chunk['choices'][0]['delta']['content'];
                                    
                                    // Reformat the chunk for our client
                                    $response_chunk = [
                                        'id' => 'chatcmpl-' . uniqid(),
                                        'object' => 'chat.completion.chunk',
                                        'created' => time(),
                                        'model' => $data['model'],
                                        'choices' => [
                                            [
                                                'index' => 0,
                                                'delta' => [
                                                    'content' => $content
                                                ],
                                                'finish_reason' => null
                                            ]
                                        ]
                                    ];
                                    echo "data: " . json_encode($response_chunk) . "\n\n";
                                    flush();
                                    ob_flush();
                                    log_message('debug', '[STREAMING] Sent chunk: ' . substr($content, 0, 50) . '...');
                                }
                            }
                        }
                        
                        return strlen($data_chunk);
                    });

                    // Execute the streaming request
                    log_message('debug', '[STREAMING] Executing curl request');
                    $result = curl_exec($curl);
                    $err = curl_error($curl);
                    $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    
                    log_message('debug', '[STREAMING] Curl completed - Status: ' . $status_code . ', Error: ' . ($err ?: 'none'));
                    
                    curl_close($curl);

                    if ($err) {
                        log_message('error', '[STREAMING] Curl error: ' . $err);
                        throw new \Exception('Error making request to provider: ' . $err);
                    }

                    if ($status_code >= 400) {
                        log_message('error', '[STREAMING] Provider error status: ' . $status_code);
                        throw new \Exception('Provider returned error status: ' . $status_code);
                    }

                    log_message('debug', '[STREAMING] Request completed successfully');

                } catch (\Exception $e) {
                    log_message('error', '[STREAMING] Exception: ' . $e->getMessage());
                    // Send error as SSE event
                    echo "event: error\n";
                    echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
                    flush();
                    ob_flush();
                }
            };
        } else {
            // For non-streaming responses, return the parsed JSON
            $response = curl_exec($curl);
            $err = curl_error($curl);
            $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($err) {
                throw new \Exception('Error making request to provider: ' . $err);
            }

            if ($status_code >= 400) {
                // Try to parse error response
                $error = json_decode($response, true);
                if (isset($error['error'])) {
                    throw new \Exception($error['error']['message'] ?? 'Unknown provider error');
                }
                throw new \Exception('Provider returned error status: ' . $status_code);
            }

            // Ensure we decode as array
            $result = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Error decoding JSON response: ' . json_last_error_msg());
            }

            return $result;
        }
    }

    abstract public function process_request(string $model, array $messages, array $options = []): array;
    abstract public function process_stream_request(string $model, array $messages, array $options = []): callable;
    abstract public function get_token_usage(array $messages): array;
}
