<?php

namespace App\Libraries\LlmProviders;

/**
 * Anthropic provider — Messages API (/v1/messages).
 *
 * Self-contained (does NOT use BaseLlmProvider::make_request) because Anthropic
 * differs from the OpenAI-shaped base on three axes:
 *   - Auth header: `x-api-key` instead of `Authorization: Bearer`.
 *   - Request body: top-level `system` + `messages[]`, and required `max_tokens`.
 *   - Streaming: SSE `content_block_delta` events, not `choices[].delta.content`.
 *
 * The legacy Text Completions API (`/v1/complete`, `prompt`,
 * `max_tokens_to_sample`) this class used before never worked with Claude 3+
 * and has been retired.
 *
 * `temperature` is intentionally omitted: Opus 4.7/4.8 and Fable 5 reject it
 * with a 400. The model default is fine for text enhancement.
 */
class AnthropicProvider extends BaseLlmProvider
{
    private const API_VERSION = '2023-06-01';

    public function process_request(string $model, array $messages, array $options = []): array
    {
        [$system, $chat_messages] = $this->_split_messages($messages);

        $data = [
            'model'      => $model,
            'max_tokens' => $options['max_tokens'] ?? 2000,
            'messages'   => $chat_messages,
            'stream'     => false,
        ];
        if ($system !== '') {
            $data['system'] = $system;
        }

        $response = $this->_anthropic_request($this->endpoint . '/v1/messages', $data);

        // Concatenate all text blocks from the response content array.
        $text = '';
        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'];
            }
        }

        return [
            'response'     => $text,
            'tokens_in'    => $response['usage']['input_tokens'] ?? $this->get_token_usage($messages)['tokens_in'],
            'tokens_out'   => $response['usage']['output_tokens'] ?? (int) ceil(strlen($text) / 4),
            'raw_response' => $response,
        ];
    }

    public function process_stream_request(string $model, array $messages, array $options = []): callable
    {
        [$system, $chat_messages] = $this->_split_messages($messages);

        $data = [
            'model'      => $model,
            'max_tokens' => $options['max_tokens'] ?? 2000,
            'messages'   => $chat_messages,
            'stream'     => true,
        ];
        if ($system !== '') {
            $data['system'] = $system;
        }

        return $this->_anthropic_stream($this->endpoint . '/v1/messages', $data, $model);
    }

    public function get_token_usage(array $messages): array
    {
        $total_chars = 0;
        foreach ($messages as $message) {
            $content = $message['content'] ?? '';
            if (is_array($content)) {
                foreach ($content as $part) {
                    if (is_string($part)) {
                        $total_chars += strlen($part);
                    } elseif (is_array($part) && isset($part['text'])) {
                        $total_chars += strlen($part['text']);
                    }
                }
            } else {
                $total_chars += strlen((string) $content);
            }
        }

        return [
            'tokens_in'  => (int) ceil($total_chars / 4),
            'tokens_out' => 0,
        ];
    }

    /**
     * Split the incoming messages into a top-level `system` string and a
     * user/assistant `messages` array, as the Messages API requires.
     *
     * @return array{0: string, 1: array}
     */
    private function _split_messages(array $messages): array
    {
        $system   = [];
        $chat     = [];

        foreach ($messages as $message) {
            $message = is_object($message) ? (array) $message : $message;
            $role    = $message['role'] ?? 'user';
            $content = $this->_flatten_content($message['content'] ?? '');

            if ($role === 'system') {
                if ($content !== '') {
                    $system[] = $content;
                }
                continue;
            }

            // Messages API only accepts 'user' and 'assistant' roles.
            $chat[] = [
                'role'    => $role === 'assistant' ? 'assistant' : 'user',
                'content' => $content,
            ];
        }

        // The conversation must start with a user turn.
        if (empty($chat) || $chat[0]['role'] !== 'user') {
            array_unshift($chat, ['role' => 'user', 'content' => '']);
        }

        return [implode("\n\n", $system), $chat];
    }

    /**
     * Flatten message content (string or multimodal array) to plain text.
     * Mirrors the prior text-only behavior; image parts are not forwarded.
     */
    private function _flatten_content($content): string
    {
        if (is_string($content)) {
            return $content;
        }

        if (is_array($content)) {
            $parts = [];
            foreach ($content as $part) {
                if (is_string($part)) {
                    $parts[] = $part;
                } elseif (is_array($part) && isset($part['text'])) {
                    $parts[] = $part['text'];
                }
            }
            return implode("\n", $parts);
        }

        return (string) $content;
    }

    /**
     * Non-streaming request to the Anthropic Messages API.
     */
    private function _anthropic_request(string $url, array $data)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => 'identity',
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => $this->_headers(),
        ]);

        $response    = curl_exec($curl);
        $err         = curl_error($curl);
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($err) {
            throw new \Exception('Error making request to Anthropic: ' . $err);
        }

        $result = json_decode($response, true);

        if ($status_code >= 400) {
            $message = $result['error']['message'] ?? 'Anthropic returned error status: ' . $status_code;
            throw new \Exception($message);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Error decoding Anthropic response: ' . json_last_error_msg());
        }

        return $result;
    }

    /**
     * Streaming request. Returns a callable that streams Anthropic SSE events
     * and re-emits them as OpenAI-style `chat.completion.chunk` data the web
     * component already understands, finishing with `data: [DONE]`.
     */
    private function _anthropic_stream(string $url, array $data, string $model): callable
    {
        $headers = $this->_headers();

        return function () use ($url, $data, $headers, $model) {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL           => $url,
                CURLOPT_ENCODING      => 'identity',
                CURLOPT_TIMEOUT       => 0,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS    => json_encode($data),
                CURLOPT_HTTPHEADER    => array_merge($headers, ['Accept: text/event-stream']),
                CURLOPT_WRITEFUNCTION => function ($ch, $data_chunk) use ($model) {
                    foreach (explode("\n", $data_chunk) as $line) {
                        $line = trim($line);
                        if ($line === '' || strpos($line, 'data: ') !== 0) {
                            continue;
                        }

                        $payload = json_decode(substr($line, 6), true);
                        if (!is_array($payload)) {
                            continue;
                        }

                        // Text token delta.
                        if (($payload['type'] ?? '') === 'content_block_delta'
                            && isset($payload['delta']['text'])) {
                            $this->_emit_chunk($model, $payload['delta']['text']);
                        }

                        // End of message → close the stream for the client.
                        if (($payload['type'] ?? '') === 'message_stop') {
                            echo "data: [DONE]\n\n";
                            $this->_flush();
                        }
                    }

                    return strlen($data_chunk);
                },
            ]);

            curl_exec($curl);
            $err         = curl_error($curl);
            $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($err) {
                log_message('error', '[STREAMING][Anthropic] Curl error: ' . $err);
                echo "event: error\n";
                echo 'data: ' . json_encode(['error' => $err]) . "\n\n";
                $this->_flush();
            } elseif ($status_code >= 400) {
                log_message('error', '[STREAMING][Anthropic] Error status: ' . $status_code);
                echo "event: error\n";
                echo 'data: ' . json_encode(['error' => 'Anthropic returned error status: ' . $status_code]) . "\n\n";
                $this->_flush();
            }
        };
    }

    private function _emit_chunk(string $model, string $content): void
    {
        $chunk = [
            'id'      => 'chatcmpl-' . uniqid(),
            'object'  => 'chat.completion.chunk',
            'created' => time(),
            'model'   => $model,
            'choices' => [[
                'index'         => 0,
                'delta'         => ['content' => $content],
                'finish_reason' => null,
            ]],
        ];
        echo 'data: ' . json_encode($chunk) . "\n\n";
        $this->_flush();
    }

    private function _flush(): void
    {
        if (function_exists('ob_flush') && @ob_get_level() > 0) {
            @ob_flush();
        }
        flush();
    }

    /**
     * @return string[]
     */
    private function _headers(): array
    {
        return [
            'Content-Type: application/json',
            'x-api-key: ' . $this->api_key,
            'anthropic-version: ' . self::API_VERSION,
        ];
    }
}
