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
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->api_key
            ], $headers)
        ]);

        if ($stream) {
            // For streaming responses, return a callable that will yield chunks
            return function () use ($curl) {
                $response = curl_exec($curl);
                $err = curl_error($curl);
                $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                if ($err) {
                    throw new \Exception('Error making request to provider: ' . $err);
                }

                if ($status_code >= 400) {
                    throw new \Exception('Provider returned error status: ' . $status_code);
                }

                // Process stream response
                $lines = explode("\n", $response);
                foreach ($lines as $line) {
                    if (strlen(trim($line)) === 0) continue;
                    if (strpos($line, 'data: ') !== 0) continue;

                    $line = substr($line, 6);
                    if ($line === '[DONE]') {
                        echo "data: [DONE]\n\n";
                        flush();
                        continue;
                    }

                    $chunk = json_decode($line, true);
                    if (!$chunk) continue;

                    if (isset($chunk['choices'][0]['delta']['content'])) {
                        echo "data: " . $chunk['choices'][0]['delta']['content'] . "\n\n";
                        flush();
                    }
                }

                curl_close($curl);
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

    /**
     * Process a request through the provider
     */
    abstract public function process_request(string $model, array $messages, array $options = []): array;

    /**
     * Get token usage for a request
     */
    abstract public function get_token_usage(array $messages): array;

    /**
     * Process a streaming request through the provider
     * @return callable A function that yields response chunks
     */
    abstract public function process_stream_request(string $model, array $messages, array $options = []): callable;
}
