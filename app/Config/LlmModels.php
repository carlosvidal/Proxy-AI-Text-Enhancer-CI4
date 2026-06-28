<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Single source of truth for LLM providers and their selectable models.
 *
 * Consumed by the admin/tenant button forms (Buttons + Admin controllers).
 * Update the model lists HERE only — do not re-introduce hardcoded copies in
 * the controllers.
 *
 * Model IDs last reviewed: 2026-06.
 * - Anthropic IDs are current and authoritative (Messages API, no date suffix).
 * - OpenAI / DeepSeek / others: verify against each provider's docs before a
 *   release, since they rotate faster than this file.
 *
 * IMPORTANT compatibility note: the generic providers send `temperature` on
 * every request. OpenAI o-series reasoning models (o1/o3/o4-*) reject that and
 * use `max_completion_tokens`, so they are intentionally NOT listed here — they
 * would need changes in OpenAiProvider first. AnthropicProvider omits
 * `temperature`, so every Claude model below is safe.
 */
class LlmModels extends BaseConfig
{
    /**
     * provider key => display label
     */
    public array $providers = [
        'openai'    => 'OpenAI',
        'anthropic' => 'Anthropic Claude',
        'mistral'   => 'Mistral AI',
        'cohere'    => 'Cohere',
        'deepseek'  => 'DeepSeek',
        'google'    => 'Google Gemini',
    ];

    /**
     * provider key => [ model_id => display label ]
     */
    public array $models = [
        'openai' => [
            'gpt-4o'         => 'GPT-4o (Omni)',
            'gpt-4o-mini'    => 'GPT-4o mini',
            'gpt-4.1'        => 'GPT-4.1',
            'gpt-4.1-mini'   => 'GPT-4.1 mini',
            'gpt-4-turbo'    => 'GPT-4 Turbo',
            'gpt-3.5-turbo'  => 'GPT-3.5 Turbo',
        ],
        'anthropic' => [
            'claude-opus-4-8'   => 'Claude Opus 4.8',
            'claude-sonnet-4-6' => 'Claude Sonnet 4.6',
            'claude-haiku-4-5'  => 'Claude Haiku 4.5',
            'claude-fable-5'    => 'Claude Fable 5',
        ],
        'mistral' => [
            'mistral-large-latest'  => 'Mistral Large',
            'mistral-medium-latest' => 'Mistral Medium',
            'mistral-small-latest'  => 'Mistral Small',
        ],
        'cohere' => [
            'command-r-plus' => 'Command R+',
            'command-r'      => 'Command R',
            'command'        => 'Command',
        ],
        'deepseek' => [
            'deepseek-chat'     => 'DeepSeek Chat (V3)',
            'deepseek-reasoner' => 'DeepSeek Reasoner (R1)',
        ],
        'google' => [
            'gemini-2.0-flash'   => 'Gemini 2.0 Flash',
            'gemini-1.5-pro'     => 'Gemini 1.5 Pro',
            'gemini-1.5-flash'   => 'Gemini 1.5 Flash',
        ],
    ];
}
