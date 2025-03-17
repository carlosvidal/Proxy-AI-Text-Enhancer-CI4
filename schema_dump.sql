
Warning: Use of undefined constant ENVIRONMENT - assumed 'ENVIRONMENT' (this will throw an Error in a future version of PHP) in /Users/carlosvidal/www/Proxy-AI-Text-Enhancer-CI4/vendor/codeigniter4/framework/system/Config/Factories.php on line 218

Warning: Use of undefined constant ENVIRONMENT - assumed 'ENVIRONMENT' (this will throw an Error in a future version of PHP) in /Users/carlosvidal/www/Proxy-AI-Text-Enhancer-CI4/app/Config/Database.php on line 87

Warning: Use of undefined constant ENVIRONMENT - assumed 'ENVIRONMENT' (this will throw an Error in a future version of PHP) in /Users/carlosvidal/www/Proxy-AI-Text-Enhancer-CI4/vendor/codeigniter4/framework/system/Database/Config.php on line 64
-- Generated SQLite Schema Dump

-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id INTEGER NULL  PRIMARY KEY,
    username VARCHAR NOT NULL  ,
    email VARCHAR NOT NULL  ,
    password VARCHAR NOT NULL  ,
    name VARCHAR NOT NULL  ,
    role TEXT NOT NULL DEFAULT 'tenant' ,
    tenant_id VARCHAR NULL  ,
    active INT NOT NULL DEFAULT 1 ,
    last_login DATETIME NULL  ,
    created_at DATETIME NOT NULL  ,
    updated_at DATETIME NOT NULL  
);

CREATE  INDEX IF NOT EXISTS users_tenant_id ON users (tenant_id);
CREATE UNIQUE INDEX IF NOT EXISTS sqlite_autoindex_users_2 ON users (email);
CREATE UNIQUE INDEX IF NOT EXISTS sqlite_autoindex_users_1 ON users (username);

-- Table: tenants
CREATE TABLE IF NOT EXISTS tenants (
    tenant_id VARCHAR NOT NULL  PRIMARY KEY,
    name VARCHAR NOT NULL  ,
    email VARCHAR NOT NULL  ,
    active INTEGER NOT NULL DEFAULT 1 ,
    created_at DATETIME NOT NULL  ,
    updated_at DATETIME NOT NULL  ,
    plan_code VARCHAR(50) NULL  ,
    subscription_status VARCHAR(20) NULL DEFAULT 'trial' ,
    trial_ends_at DATETIME NULL  ,
    subscription_ends_at DATETIME NULL  ,
    max_domains INTEGER NULL DEFAULT 1 ,
    max_api_keys INTEGER NOT NULL DEFAULT 1 
);


-- Table: buttons
CREATE TABLE IF NOT EXISTS buttons (
    id INTEGER NULL  PRIMARY KEY,
    tenant_id VARCHAR NOT NULL  ,
    button_id VARCHAR NOT NULL  ,
    name VARCHAR NOT NULL  ,
    domain VARCHAR NOT NULL  ,
    provider VARCHAR NOT NULL  ,
    model VARCHAR NOT NULL  ,
    api_key TEXT NULL  ,
    system_prompt TEXT NULL  ,
    active INT NOT NULL DEFAULT 1 ,
    created_at DATETIME NOT NULL  ,
    updated_at DATETIME NOT NULL  
);

CREATE UNIQUE INDEX IF NOT EXISTS buttons_button_id_unique ON buttons (button_id);
CREATE UNIQUE INDEX IF NOT EXISTS buttons_button_id ON buttons (button_id);
CREATE  INDEX IF NOT EXISTS buttons_tenant_id ON buttons (tenant_id);
CREATE UNIQUE INDEX IF NOT EXISTS sqlite_autoindex_buttons_1 ON buttons (button_id);

-- Table: plans
CREATE TABLE IF NOT EXISTS plans (
    id INTEGER NULL  PRIMARY KEY,
    name VARCHAR NOT NULL  ,
    code VARCHAR NOT NULL  ,
    price DECIMAL NOT NULL  ,
    requests_limit INTEGER NOT NULL DEFAULT 0 ,
    users_limit INTEGER NOT NULL DEFAULT 0 ,
    features TEXT NULL  ,
    created_at DATETIME NULL  ,
    updated_at DATETIME NULL  
);

CREATE UNIQUE INDEX IF NOT EXISTS plans_code ON plans (code);

-- Table: usage_logs
CREATE TABLE IF NOT EXISTS usage_logs (
    id INTEGER NULL  PRIMARY KEY,
    tenant_id INTEGER NOT NULL  ,
    user_id INTEGER NULL  ,
    button_id INTEGER NULL  ,
    api_user_id VARCHAR NULL  ,
    tokens INTEGER NOT NULL DEFAULT 0 ,
    cost DECIMAL NOT NULL DEFAULT 0 ,
    input_length INTEGER NOT NULL DEFAULT 0 ,
    output_length INTEGER NOT NULL DEFAULT 0 ,
    status VARCHAR NOT NULL DEFAULT 'success' ,
    created_at DATETIME NULL  ,
    updated_at DATETIME NULL  
);

CREATE  INDEX IF NOT EXISTS usage_logs_created_at ON usage_logs (created_at);
CREATE  INDEX IF NOT EXISTS usage_logs_user_id ON usage_logs (user_id);
CREATE  INDEX IF NOT EXISTS usage_logs_tenant_id ON usage_logs (tenant_id);

-- Table: tenant_users
CREATE TABLE IF NOT EXISTS tenant_users (
    id INTEGER NULL  PRIMARY KEY,
    tenant_id VARCHAR NOT NULL  ,
    user_id VARCHAR NOT NULL  ,
    name VARCHAR NOT NULL  ,
    email VARCHAR NULL  ,
    quota INTEGER NOT NULL DEFAULT 1000 ,
    active INTEGER NOT NULL DEFAULT 1 ,
    created_at DATETIME NOT NULL  ,
    updated_at DATETIME NOT NULL  
);

CREATE UNIQUE INDEX IF NOT EXISTS tenant_users_user_id ON tenant_users (user_id);

-- Table: tokens
CREATE TABLE IF NOT EXISTS tokens (
    id INTEGER NULL  PRIMARY KEY,
    tenant_id VARCHAR(50) NOT NULL  ,
    button_id VARCHAR(50) NOT NULL  ,
    tokens INTEGER NULL DEFAULT 0 ,
    created_at DATETIME NOT NULL  ,
    updated_at DATETIME NOT NULL  
);


-- Table: api_users
CREATE TABLE IF NOT EXISTS api_users (
    user_id VARCHAR(32) NOT NULL  PRIMARY KEY,
    external_id VARCHAR(255) NOT NULL  ,
    tenant_id VARCHAR(32) NOT NULL  ,
    name VARCHAR(255) NULL  ,
    email VARCHAR(255) NULL  ,
    quota INTEGER NOT NULL DEFAULT 100000 ,
    active TINYINT(1) NOT NULL DEFAULT 1 ,
    created_at DATETIME NOT NULL  ,
    updated_at DATETIME NOT NULL  
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_api_users_external_tenant ON api_users (external_id, tenant_id);
CREATE  INDEX IF NOT EXISTS idx_api_users_tenant_id ON api_users (tenant_id);

-- Table: domains
CREATE TABLE IF NOT EXISTS domains (
    domain_id VARCHAR(32) NULL  PRIMARY KEY,
    tenant_id VARCHAR(32) NOT NULL  ,
    domain VARCHAR(255) NOT NULL  ,
    verified TINYINT NULL DEFAULT 0 ,
    created_at DATETIME NULL  
);


-- Table: tenant_api_keys
CREATE TABLE IF NOT EXISTS tenant_api_keys (
    id INTEGER NULL  PRIMARY KEY,
    api_key_id VARCHAR(50) NOT NULL  ,
    tenant_id VARCHAR(50) NOT NULL  ,
    name VARCHAR(100) NOT NULL  ,
    provider VARCHAR(50) NOT NULL  ,
    api_key TEXT NOT NULL  ,
    is_default TINYINT(1) NOT NULL DEFAULT 0 ,
    active TINYINT(1) NOT NULL DEFAULT 1 ,
    created_at DATETIME NOT NULL  ,
    updated_at DATETIME NOT NULL  
);

CREATE UNIQUE INDEX IF NOT EXISTS sqlite_autoindex_tenant_api_keys_1 ON tenant_api_keys (api_key_id);

