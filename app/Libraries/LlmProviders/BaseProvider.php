<?php

namespace App\Libraries\LlmProviders;

abstract class BaseProvider
{
    /**
     * Process a regular request
     */
    abstract public function process_request($model, $messages, $options = []);

    /**
     * Process a streaming request
     */
    abstract public function process_stream_request($model, $messages, $options = []);

    /**
     * Get token usage for a request
     */
    abstract public function get_token_usage($messages);
}
