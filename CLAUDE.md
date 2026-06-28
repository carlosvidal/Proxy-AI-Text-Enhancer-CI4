# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A multi-tenant **LLM proxy** built on CodeIgniter 4 (PHP 7.4+/8.x, SQLite). It unifies multiple LLM providers (OpenAI, Anthropic, Mistral, DeepSeek, Google, Azure) behind a single API, adding per-tenant/per-user quota enforcement, usage logging, CORS-by-domain authorization, response caching, and streaming. It is the backend for the `ai-text-enhancer` web component; browsers call it directly, so CORS and domain authorization are first-class concerns.

## Commands

```bash
# Tests (PHPUnit)
composer test                          # run full suite
vendor/bin/phpunit                     # same
vendor/bin/phpunit tests/unit/HealthTest.php          # single file
vendor/bin/phpunit --filter testMethodName            # single test

# Local dev server (CodeIgniter spark)
php spark serve                        # http://localhost:8080

# Database migrations — CLI
php spark migrate                      # apply pending migrations
php spark migrate:status
php spark migrate:rollback

# Migrations are ALSO exposed over HTTP (superadmin-only), used in production:
#   GET /migrate, /migrate/status, /migrate/reset, /migrate/version/(:num)

# Custom maintenance commands (app/Commands/)
php spark CheckApiKeys
php spark CheckButtonsTable
```

There is no build step and no JS lint/test here — the `ai-text-enhancer` web component lives in a separate repo; this repo serves its prebuilt bundle from `public/`.

## Architecture

**Request flow for an LLM call** (`POST /api/llm-proxy`, all logic in [app/Controllers/LlmProxy.php](app/Controllers/LlmProxy.php), ~1200 lines):
1. CORS headers set manually (`_set_cors_headers`) — origin validated against authorized domains.
2. Request JSON parsed. Accepts both camelCase and snake_case keys (`userId`/`user_id`, `buttonId`/`button_id`, `tenantId`).
3. **Button-driven config**: if `button_id` is present, `provider`/`model`/`api_key_id` are loaded from the `buttons` table (status must be `active`), overriding any provider/model in the request body. The request body values are only fallbacks.
4. The provider's API key is decrypted from the `api_keys` table (or falls back to `.env`).
5. `_get_provider($provider, $api_key)` (the `switch` near line 1006) instantiates the matching provider class.
6. Provider makes the upstream call. Streaming returns a callable that yields SSE chunks; non-streaming returns an array.
7. Usage is recorded in `usage_logs` (tokens, cost, provider, model, button, status) via `UsageLogsModel`.

**Provider abstraction** — [app/Libraries/LlmProviders/](app/Libraries/LlmProviders/): each provider extends `BaseLlmProvider` (raw cURL in `make_request`, handles both streaming and buffered modes) and implements `LlmProviderInterface`. To add a provider: create the class, add a `case` to the `switch` in `LlmProxy::_get_provider`, and register its endpoint in `_initialize_config`. Compression is deliberately disabled on upstream requests (`Accept-Encoding: identity`) to keep SSE streaming intact.

**Two distinct authentication systems — do not conflate them:**
- **Admin panel** (server-rendered views): session-based login (`Auth` controller, `users` table). Access is gated by the `auth` filter, which takes a role argument: `auth` (any logged-in), `auth:tenant`, `auth:superadmin`. Roles: `superadmin` (everything under `admin/*`) and `tenant` (manages own buttons, api-keys, api-users, domains). See [app/Filters/AuthFilter.php](app/Filters/AuthFilter.php).
- **API** (`/api/*`): either open (`/api/llm-proxy`) or JWT-protected (`/api/llm-proxy/secure`, `/api/quota/secure`) via the `jwt` filter. JWT helpers in [app/Helpers/jwt_helper.php](app/Helpers/jwt_helper.php) (firebase/php-jwt).

**CORS / domain authorization** — [app/Filters/CorsFilter.php](app/Filters/CorsFilter.php): allowed origins are computed from the database, not a static list. It unions domains from active `buttons` and the `domains` table. A button `domain` of `__tenant__` is a sentinel meaning "resolve to this tenant's verified domains in the `domains` table." Domains may be comma-separated. Results are cached per-request. When touching CORS, remember origins are matched with protocol (`https://`).

**Multi-tenancy & data model** — see [docs/database-schema.md](docs/database-schema.md) for the full schema (12 tables). Key entities: `tenants` (billing/limits root) → `buttons` (a named provider+model+prompt+domain config, the primary unit end-users invoke) → `api_keys` (encrypted provider credentials) → `api_users` (end users with per-user/daily quotas) → `usage_logs`. IDs are application-generated strings (`ten-…`, `btn-…`, `key-…`, `usr-…`), not autoincrement. SQLite does **not** enforce foreign keys here — relationships are logical only.

**API keys are encrypted at rest.** Always use [app/Helpers/api_key_helper.php](app/Helpers/api_key_helper.php) (`encrypt_api_key` / `decrypt_api_key` / `mask_api_key`) — never store or read the raw column directly. Encryption is configured in [app/Config/Encryption.php](app/Config/Encryption.php) (key from `.env`).

## Conventions in this codebase

- **Logging is verbose and structured.** Use the `log_info`/`log_debug`/`log_error` helpers ([app/Helpers/log_helper.php](app/Helpers/log_helper.php)) with a category tag (e.g. `'PROXY'`, `'CORS'`) and a context array, matching existing call sites. Logs are written under `writable/logs/`.
- Controllers load helpers explicitly in their constructor via `helper([...])`.
- Much of the LLM proxy logic queries the DB with raw `$db->query(...)` rather than models — match the surrounding style when editing `LlmProxy.php`.
- Mixed Spanish/English: comments, log messages, and docs are frequently Spanish; code identifiers are English. Keep new comments consistent with the file you're in.
- The repo root contains many ad-hoc `*.php` debug/setup scripts (`debug_*.php`, `setup_demo_*.php`, `fix_*.php`, `check_*.php`) and loose `.sql` files. These are throwaway operational scripts, not part of the app — don't treat them as architecture, and prefer `app/Commands/` for anything reusable.

## Config & environment

- Copy `env` → `.env` and set provider API keys (`OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, etc.) plus `app.baseURL` and the encryption key.
- Database is SQLite (`database.sqlite` at repo root by default); configured in [app/Config/Database.php](app/Config/Database.php).
- API docs: [openapi.yaml](openapi.yaml) and the Postman collection in [docs/](docs/).
