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
                    throw new \Exception('Provider API error: ' . $response);
                }

                return $response;
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
                throw new \Exception('Provider API error: ' . $response);
            }

            // Ensure we get an array back, not an object
            $decoded = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Error decoding JSON response: ' . json_last_error_msg());
            }

            return $decoded;
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
