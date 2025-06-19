-- AI Text Enhancer Proxy - Sample Data
-- This file contains sample data for testing and development

-- Clear existing data (be careful in production!)
-- DELETE FROM usage_logs;
-- DELETE FROM api_users;
-- DELETE FROM domains;
-- DELETE FROM buttons;
-- DELETE FROM api_keys;
-- DELETE FROM tenant_users;
-- DELETE FROM tenants;
-- DELETE FROM plans;
-- DELETE FROM users;

-- =====================================
-- PLANS - Subscription Plans
-- =====================================

INSERT INTO plans (name, code, price, requests_limit, users_limit, features, created_at, updated_at) VALUES
('Free Trial', 'free', 0.00, 1000, 5, '{"api_access": true, "support": "community"}', datetime('now'), datetime('now')),
('Starter', 'starter', 29.99, 10000, 25, '{"api_access": true, "support": "email", "analytics": true}', datetime('now'), datetime('now')),
('Professional', 'pro', 99.99, 50000, 100, '{"api_access": true, "support": "priority", "analytics": true, "custom_models": true}', datetime('now'), datetime('now')),
('Enterprise', 'enterprise', 299.99, 200000, 500, '{"api_access": true, "support": "dedicated", "analytics": true, "custom_models": true, "sla": true}', datetime('now'), datetime('now'));

-- =====================================
-- USERS - Administrative Users
-- =====================================

INSERT INTO users (username, email, password, name, role, tenant_id, active, quota, last_login, created_at, updated_at) VALUES
-- Superadmin user
('admin', 'admin@aitextenhancer.com', '$2y$10$example_hashed_password', 'System Administrator', 'superadmin', NULL, 1, NULL, NULL, datetime('now'), datetime('now')),
-- Tenant admin users
('demo_admin', 'admin@democorp.com', '$2y$10$example_hashed_password', 'Demo Corp Admin', 'admin', 'ten-684cc05b-5d6457e5', 1, 50000, datetime('now', '-1 day'), datetime('now'), datetime('now')),
('acme_admin', 'admin@acme.com', '$2y$10$example_hashed_password', 'ACME Corp Admin', 'admin', 'ten-67d88d1d-111ae225', 1, 25000, datetime('now', '-2 hours'), datetime('now'), datetime('now'));

-- =====================================
-- TENANTS - Organizations
-- =====================================

INSERT INTO tenants (tenant_id, name, email, quota, active, api_key, plan_code, subscription_status, trial_ends_at, subscription_ends_at, max_domains, max_api_keys, auto_create_users, created_at, updated_at) VALUES
-- Demo tenant for testing
('ten-684cc05b-5d6457e5', 'Demo Corporation', 'contact@democorp.com', 50000, 1, NULL, 'pro', 'active', NULL, '2024-12-31 23:59:59', 5, 3, 1, datetime('now', '-30 days'), datetime('now')),
-- Production tenant
('ten-67d88d1d-111ae225', 'ACME Industries', 'billing@acme.com', 25000, 1, NULL, 'starter', 'active', NULL, '2024-06-30 23:59:59', 3, 2, 1, datetime('now', '-15 days'), datetime('now')),
-- Trial tenant
('ten-trial123-456789ab', 'Startup XYZ', 'hello@startupxyz.com', 1000, 1, NULL, 'free', 'trial', '2024-07-01 23:59:59', NULL, 2, 1, 1, datetime('now', '-5 days'), datetime('now'));

-- =====================================
-- API KEYS - Provider Credentials
-- =====================================

INSERT INTO api_keys (api_key_id, tenant_id, name, provider, api_key, is_default, active, created_at, updated_at) VALUES
-- Demo Corp API Keys
('key-openai-demo-001', 'ten-684cc05b-5d6457e5', 'OpenAI Production Key', 'openai', 'encrypted_sk_proj_example_key_here', 1, 1, datetime('now', '-25 days'), datetime('now')),
('key-anthropic-demo-001', 'ten-684cc05b-5d6457e5', 'Anthropic Claude Key', 'anthropic', 'encrypted_claude_key_here', 1, 1, datetime('now', '-20 days'), datetime('now')),
-- ACME Corp API Keys
('key-openai-acme-001', 'ten-67d88d1d-111ae225', 'ACME OpenAI Key', 'openai', 'encrypted_sk_proj_acme_key_here', 1, 1, datetime('now', '-10 days'), datetime('now')),
-- Startup XYZ API Keys
('key-openai-startup-001', 'ten-trial123-456789ab', 'Startup Trial Key', 'openai', 'encrypted_trial_key_here', 1, 1, datetime('now', '-3 days'), datetime('now'));

-- =====================================
-- DOMAINS - Authorized Domains
-- =====================================

INSERT INTO domains (domain_id, tenant_id, domain, verified, created_at) VALUES
-- Demo Corp domains
('dom-demo-001', 'ten-684cc05b-5d6457e5', 'democorp.com', 1, datetime('now', '-25 days')),
('dom-demo-002', 'ten-684cc05b-5d6457e5', 'app.democorp.com', 1, datetime('now', '-20 days')),
('dom-demo-003', 'ten-684cc05b-5d6457e5', 'localhost:3000', 1, datetime('now', '-15 days')),
-- ACME Corp domains
('dom-acme-001', 'ten-67d88d1d-111ae225', 'acme.com', 1, datetime('now', '-10 days')),
('dom-acme-002', 'ten-67d88d1d-111ae225', 'portal.acme.com', 1, datetime('now', '-8 days')),
-- Startup XYZ domains
('dom-startup-001', 'ten-trial123-456789ab', 'startupxyz.com', 0, datetime('now', '-3 days'));

-- =====================================
-- BUTTONS - AI Model Configurations
-- =====================================

INSERT INTO buttons (button_id, tenant_id, name, description, domain, system_prompt, provider, model, api_key_id, status, auto_create_api_users, created_at, updated_at) VALUES
-- Demo Corp buttons
('btn-demo-enhancer', 'ten-684cc05b-5d6457e5', 'Content Enhancer', 'Professional content enhancement for marketing materials', 'democorp.com', 'You are a professional content enhancer. Improve the text while maintaining its core message and intent. Focus on clarity, engagement, and professionalism.', 'openai', 'gpt-4', 'key-openai-demo-001', 'active', 1, datetime('now', '-25 days'), datetime('now')),
('btn-demo-summarizer', 'ten-684cc05b-5d6457e5', 'Document Summarizer', 'Intelligent document summarization for reports', 'app.democorp.com', 'You are an expert document summarizer. Create concise, accurate summaries that capture key points and insights.', 'anthropic', 'claude-3-sonnet-20240229', 'key-anthropic-demo-001', 'active', 1, datetime('now', '-20 days'), datetime('now')),
('btn-demo-translator', 'ten-684cc05b-5d6457e5', 'Smart Translator', 'Context-aware translation with cultural adaptation', 'democorp.com', 'You are a professional translator. Translate text accurately while preserving tone, context, and cultural nuances.', 'openai', 'gpt-4', 'key-openai-demo-001', 'active', 1, datetime('now', '-15 days'), datetime('now')),
-- ACME Corp buttons
('btn-acme-support', 'ten-67d88d1d-111ae225', 'Customer Support Assistant', 'AI-powered customer support responses', 'acme.com', 'You are a helpful customer support assistant for ACME Industries. Provide professional, empathetic, and solution-focused responses.', 'openai', 'gpt-3.5-turbo', 'key-openai-acme-001', 'active', 1, datetime('now', '-10 days'), datetime('now')),
('btn-acme-qa', 'ten-67d88d1d-111ae225', 'Technical Q&A', 'Technical documentation and FAQ assistant', 'portal.acme.com', 'You are a technical documentation expert. Provide clear, accurate answers based on ACME''s product specifications.', 'openai', 'gpt-4', 'key-openai-acme-001', 'active', 1, datetime('now', '-8 days'), datetime('now')),
-- Startup XYZ buttons
('btn-startup-basic', 'ten-trial123-456789ab', 'Basic Text Helper', 'Simple text improvement for trial users', 'startupxyz.com', 'You are a helpful writing assistant. Improve the clarity and readability of the provided text.', 'openai', 'gpt-3.5-turbo', 'key-openai-startup-001', 'active', 1, datetime('now', '-3 days'), datetime('now'));

-- =====================================
-- API USERS - End Users
-- =====================================

INSERT INTO api_users (user_id, tenant_id, external_id, name, email, quota, daily_quota, active, created_at, updated_at, last_activity) VALUES
-- Demo Corp users
('usr-684ce34a-5c1ac895', 'ten-684cc05b-5d6457e5', 'DEMO', 'Demo User', 'demo@democorp.com', 10000, 1000, 1, datetime('now', '-20 days'), datetime('now'), datetime('now', '-2 hours')),
('usr-demo-marketing', 'ten-684cc05b-5d6457e5', 'MARKETING_TEAM', 'Marketing Team', 'marketing@democorp.com', 15000, 1500, 1, datetime('now', '-18 days'), datetime('now'), datetime('now', '-1 hour')),
('usr-demo-support', 'ten-684cc05b-5d6457e5', 'SUPPORT_BOT', NULL, NULL, 5000, 500, 1, datetime('now', '-15 days'), datetime('now'), datetime('now', '-30 minutes')),
-- ACME Corp users
('usr-acme-001', 'ten-67d88d1d-111ae225', 'ACME_USER_001', 'John Smith', 'john@acme.com', 8000, 800, 1, datetime('now', '-8 days'), datetime('now'), datetime('now', '-4 hours')),
('usr-acme-002', 'ten-67d88d1d-111ae225', 'ACME_USER_002', 'Jane Doe', 'jane@acme.com', 8000, 800, 1, datetime('now', '-6 days'), datetime('now'), datetime('now', '-1 day')),
('usr-acme-support', 'ten-67d88d1d-111ae225', 'SUPPORT_SYSTEM', NULL, NULL, 12000, 1200, 1, datetime('now', '-5 days'), datetime('now'), datetime('now', '-20 minutes')),
-- Startup XYZ users
('usr-startup-001', 'ten-trial123-456789ab', 'FOUNDER', 'Alex Johnson', 'alex@startupxyz.com', 800, 100, 1, datetime('now', '-3 days'), datetime('now'), datetime('now', '-6 hours'));

-- =====================================
-- USAGE LOGS - API Usage History
-- =====================================

INSERT INTO usage_logs (usage_id, tenant_id, user_id, external_id, button_id, provider, model, tokens, cost, has_image, status, created_at, updated_at) VALUES
-- Recent usage for Demo Corp
('usage-demo-001', 'ten-684cc05b-5d6457e5', 0, 'DEMO', 'btn-demo-enhancer', 'openai', 'gpt-4', 450, 0.009, 0, 'success', datetime('now', '-2 hours'), datetime('now', '-2 hours')),
('usage-demo-002', 'ten-684cc05b-5d6457e5', 0, 'DEMO', 'btn-demo-enhancer', 'openai', 'gpt-4', 320, 0.0064, 0, 'success', datetime('now', '-1 hour'), datetime('now', '-1 hour')),
('usage-demo-003', 'ten-684cc05b-5d6457e5', 0, 'MARKETING_TEAM', 'btn-demo-summarizer', 'anthropic', 'claude-3-sonnet-20240229', 680, 0.0204, 0, 'success', datetime('now', '-45 minutes'), datetime('now', '-45 minutes')),
('usage-demo-004', 'ten-684cc05b-5d6457e5', 0, 'MARKETING_TEAM', 'btn-demo-translator', 'openai', 'gpt-4', 520, 0.0104, 0, 'success', datetime('now', '-30 minutes'), datetime('now', '-30 minutes')),
-- ACME Corp usage
('usage-acme-001', 'ten-67d88d1d-111ae225', 0, 'ACME_USER_001', 'btn-acme-support', 'openai', 'gpt-3.5-turbo', 280, 0.00042, 0, 'success', datetime('now', '-4 hours'), datetime('now', '-4 hours')),
('usage-acme-002', 'ten-67d88d1d-111ae225', 0, 'SUPPORT_SYSTEM', 'btn-acme-support', 'openai', 'gpt-3.5-turbo', 195, 0.000293, 0, 'success', datetime('now', '-20 minutes'), datetime('now', '-20 minutes')),
('usage-acme-003', 'ten-67d88d1d-111ae225', 0, 'ACME_USER_002', 'btn-acme-qa', 'openai', 'gpt-4', 340, 0.0068, 0, 'success', datetime('now', '-1 day'), datetime('now', '-1 day')),
-- Startup usage
('usage-startup-001', 'ten-trial123-456789ab', 0, 'FOUNDER', 'btn-startup-basic', 'openai', 'gpt-3.5-turbo', 150, 0.000225, 0, 'success', datetime('now', '-6 hours'), datetime('now', '-6 hours')),
-- Historical usage for analytics (past week)
('usage-demo-hist-001', 'ten-684cc05b-5d6457e5', 0, 'DEMO', 'btn-demo-enhancer', 'openai', 'gpt-4', 380, 0.0076, 0, 'success', datetime('now', '-2 days'), datetime('now', '-2 days')),
('usage-demo-hist-002', 'ten-684cc05b-5d6457e5', 0, 'DEMO', 'btn-demo-enhancer', 'openai', 'gpt-4', 420, 0.0084, 0, 'success', datetime('now', '-3 days'), datetime('now', '-3 days')),
('usage-demo-hist-003', 'ten-684cc05b-5d6457e5', 0, 'MARKETING_TEAM', 'btn-demo-summarizer', 'anthropic', 'claude-3-sonnet-20240229', 720, 0.0216, 0, 'success', datetime('now', '-4 days'), datetime('now', '-4 days')),
('usage-acme-hist-001', 'ten-67d88d1d-111ae225', 0, 'ACME_USER_001', 'btn-acme-support', 'openai', 'gpt-3.5-turbo', 210, 0.000315, 0, 'success', datetime('now', '-5 days'), datetime('now', '-5 days')),
('usage-acme-hist-002', 'ten-67d88d1d-111ae225', 0, 'SUPPORT_SYSTEM', 'btn-acme-support', 'openai', 'gpt-3.5-turbo', 165, 0.000248, 0, 'success', datetime('now', '-6 days'), datetime('now', '-6 days'));

-- =====================================
-- TENANT USERS - Admin Access
-- =====================================

INSERT INTO tenant_users (tenant_id, user_id, name, email, quota, active, created_at, updated_at) VALUES
('ten-684cc05b-5d6457e5', 'admin-demo-001', 'Demo Administrator', 'admin@democorp.com', 50000, 1, datetime('now', '-25 days'), datetime('now')),
('ten-684cc05b-5d6457e5', 'manager-demo-001', 'Marketing Manager', 'marketing.manager@democorp.com', 20000, 1, datetime('now', '-20 days'), datetime('now')),
('ten-67d88d1d-111ae225', 'admin-acme-001', 'ACME Administrator', 'admin@acme.com', 25000, 1, datetime('now', '-10 days'), datetime('now')),
('ten-trial123-456789ab', 'founder-startup', 'Startup Founder', 'alex@startupxyz.com', 1000, 1, datetime('now', '-3 days'), datetime('now'));

-- =====================================
-- USER QUOTAS - Custom Quotas
-- =====================================

INSERT INTO user_quotas (tenant_id, external_id, total_quota, created_at) VALUES
-- Custom quotas for power users
('ten-684cc05b-5d6457e5', 'MARKETING_TEAM', 15000, datetime('now', '-18 days')),
('ten-67d88d1d-111ae225', 'SUPPORT_SYSTEM', 12000, datetime('now', '-5 days'));

-- =====================================
-- API TOKENS - Administrative Access
-- =====================================

INSERT INTO api_tokens (user_id, tenant_id, name, token, refresh_token, scopes, last_used_at, expires_at, revoked, created_at, updated_at) VALUES
(2, 'ten-684cc05b-5d6457e5', 'Demo Admin Token', 'demo_admin_token_hash_here', 'demo_refresh_token_hash', 'admin:read,admin:write', datetime('now', '-1 hour'), datetime('now', '+30 days'), 0, datetime('now', '-25 days'), datetime('now')),
(3, 'ten-67d88d1d-111ae225', 'ACME Admin Token', 'acme_admin_token_hash_here', 'acme_refresh_token_hash', 'admin:read,admin:write', datetime('now', '-2 hours'), datetime('now', '+30 days'), 0, datetime('now', '-10 days'), datetime('now'));

-- =====================================
-- SAMPLE QUERIES FOR TESTING
-- =====================================

/*
-- Test quota calculations
SELECT 
    au.external_id,
    au.quota as monthly_quota,
    au.daily_quota,
    COALESCE(SUM(ul.tokens), 0) as tokens_used_today
FROM api_users au
LEFT JOIN usage_logs ul ON au.external_id = ul.external_id 
    AND au.tenant_id = ul.tenant_id
    AND date(ul.created_at) = date('now')
WHERE au.tenant_id = 'ten-684cc05b-5d6457e5'
GROUP BY au.external_id, au.quota, au.daily_quota;

-- Test button configurations
SELECT 
    b.name,
    b.provider,
    b.model,
    ak.name as api_key_name,
    COUNT(ul.id) as usage_count
FROM buttons b
JOIN api_keys ak ON b.api_key_id = ak.api_key_id
LEFT JOIN usage_logs ul ON b.button_id = ul.button_id
WHERE b.tenant_id = 'ten-684cc05b-5d6457e5'
GROUP BY b.button_id, b.name, b.provider, b.model, ak.name;

-- Test monthly usage statistics
SELECT 
    strftime('%Y-%m', created_at) as month,
    COUNT(*) as total_requests,
    SUM(tokens) as total_tokens,
    ROUND(AVG(tokens), 2) as avg_tokens_per_request,
    SUM(cost) as total_cost
FROM usage_logs 
WHERE tenant_id = 'ten-684cc05b-5d6457e5'
GROUP BY strftime('%Y-%m', created_at)
ORDER BY month DESC;
*/