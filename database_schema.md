# Esquema de Base de Datos — AI Text Enhancer (Proxy-CI4)

Este documento define todas las tablas necesarias para que la aplicación funcione correctamente, según los modelos encontrados en `app/Models`.

---

## Tabla: `users`
- `id` (PK, autoincrement)
- `username` (string, único, requerido)
- `email` (string, único, requerido)
- `password` (string, requerido)
- `name` (string, requerido)
- `role` (enum: superadmin, tenant, requerido)
- `tenant_id` (string, nullable)
- `active` (tinyint, requerido)
- `quota` (int, nullable)
- `last_login` (datetime, nullable)
- `created_at` (datetime)
- `updated_at` (datetime)

## Tabla: `tenants`
- `tenant_id` (PK, string, no autoincrement, único)
- `name` (string, requerido)
- `email` (string, requerido)
- `quota` (int, requerido)
- `active` (tinyint, requerido)
- `api_key` (string, nullable)
- `plan_code` (string, nullable)
- `subscription_status` (string, nullable)
- `trial_ends_at` (datetime, nullable)
- `subscription_ends_at` (datetime, nullable)
- `max_domains` (int, requerido)
- `max_api_keys` (int, requerido)
- `auto_create_users` (tinyint, requerido)
- `created_at` (datetime)
- `updated_at` (datetime)

## Tabla: `buttons`
- `id` (PK, autoincrement)
- `button_id` (string, único, requerido)
- `tenant_id` (string, requerido)
- `name` (string, requerido)
- `description` (string, nullable)
- `domain` (string, requerido)
- `system_prompt` (text, nullable)
- `provider` (enum: openai, anthropic, google, azure, requerido)
- `model` (string, requerido)
- `api_key_id` (string, requerido)
- `status` (enum: active, inactive, requerido)
- `auto_create_api_users` (tinyint, nullable)
- `created_at` (datetime)
- `updated_at` (datetime)

## Tabla: `api_keys`
- `api_key_id` (PK, string, único, requerido)
- `tenant_id` (string, requerido)
- `name` (string, requerido)
- `provider` (enum: openai, anthropic, cohere, mistral, deepseek, google, requerido)
- `api_key` (string, requerido)
- `is_default` (tinyint, requerido)
- `active` (tinyint, requerido)
- `created_at` (datetime)
- `updated_at` (datetime)

## Tabla: `api_tokens`
- `id` (PK, autoincrement)
- `user_id` (int, requerido)
- `tenant_id` (string, nullable)
- `name` (string, requerido)
- `token` (string, requerido, longitud 32-64)
- `refresh_token` (string, requerido)
- `scopes` (json, nullable)
- `last_used_at` (datetime, nullable)
- `expires_at` (datetime, nullable)
- `revoked` (tinyint, default 0)
- `created_at` (datetime)
- `updated_at` (datetime)

## Tabla: `api_users`
- `user_id` (PK, string, no autoincrement)
- `tenant_id` (string, requerido)
- `external_id` (string, nullable)
- `name` (string, requerido)
- `email` (string, nullable)
- `quota` (int, nullable)
- `daily_quota` (int, default 10000)
- `active` (tinyint, default 1)
- `created_at` (datetime)
- `updated_at` (datetime)
- `last_activity` (datetime, nullable)

## Tabla: `tenant_users`
- `id` (PK, autoincrement)
- `tenant_id` (string, requerido)
- `user_id` (string, único, requerido)
- `name` (string, requerido)
- `email` (string, nullable)
- `quota` (int, requerido)
- `active` (tinyint, requerido)
- `created_at` (datetime)
- `updated_at` (datetime)

## Tabla: `domains`
- `domain_id` (PK, string, no autoincrement, único)
- `tenant_id` (string, requerido)
- `domain` (string, único por tenant, requerido)
- `verified` (tinyint, default 0)
- `created_at` (datetime)

## Tabla: `plans`
- `id` (PK, autoincrement)
- `name` (string, requerido)
- `code` (string, único, requerido)
- `price` (decimal, requerido)
- `requests_limit` (int, requerido)
- `users_limit` (int, requerido)
- `features` (json/text, nullable)
- `created_at` (datetime)
- `updated_at` (datetime)

## Tabla: `usage_logs`
- `id` (PK, autoincrement)
- `usage_id` (string, único, requerido)
- `tenant_id` (string, requerido)
- `user_id` (int, nullable)
- `external_id` (string, nullable)
- `button_id` (string, nullable)
- `provider` (string, requerido)
- `model` (string, requerido)
- `tokens` (int, requerido)
- `cost` (decimal, nullable)
- `has_image` (tinyint, nullable)
- `status` (string, requerido)
- `created_at` (datetime)
- `updated_at` (datetime)

## Tabla: `user_quotas`
- `tenant_id` (string, requerido)
- `external_id` (string, requerido)
- `total_quota` (int, requerido)
- `created_at` (datetime)


---

**Notas:**
- Todos los campos `datetime` pueden ser `timestamp` o `datetime` según el soporte de SQLite.
- Los tipos `tinyint` se usan para campos booleanos (0/1).
- Los campos con `enum` pueden representarse como `varchar` con validación a nivel de aplicación.
- Los campos `json` pueden ser `text` en SQLite.
- Los nombres de claves primarias y restricciones únicas deben coincidir con los modelos para evitar errores de validación.

---

Este esquema cubre todas las tablas y columnas requeridas por los modelos de tu aplicación. Si necesitas detalles de relaciones o constraints adicionales, házmelo saber.
