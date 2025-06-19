CREATE TABLE `migrations` (
	`id` INTEGER PRIMARY KEY AUTOINCREMENT,
	`version` VARCHAR NOT NULL,
	`class` VARCHAR NOT NULL,
	`group` VARCHAR NOT NULL,
	`namespace` VARCHAR NOT NULL,
	`time` INT NOT NULL,
	`batch` INT NOT NULL
);
CREATE TABLE sqlite_sequence(name,seq);
CREATE TABLE `users` (
	`id` INTEGER PRIMARY KEY AUTOINCREMENT,
	`username` VARCHAR NOT NULL UNIQUE,
	`email` VARCHAR NOT NULL UNIQUE,
	`password` VARCHAR NOT NULL,
	`name` VARCHAR NOT NULL,
	`role` VARCHAR NOT NULL,
	`tenant_id` VARCHAR NULL,
	`active` TINYINT NOT NULL,
	`quota` INTEGER NULL,
	`last_login` DATETIME NULL,
	`created_at` DATETIME NOT NULL,
	`updated_at` DATETIME NOT NULL
);
CREATE UNIQUE INDEX `users_username` ON `users` (`username`);
CREATE UNIQUE INDEX `users_email` ON `users` (`email`);
CREATE TABLE `tenants` (
	`tenant_id` VARCHAR NOT NULL,
	`name` VARCHAR NOT NULL,
	`email` VARCHAR NOT NULL,
	`quota` INTEGER NOT NULL,
	`active` TINYINT NOT NULL,
	`api_key` VARCHAR NULL,
	`plan_code` VARCHAR NULL,
	`subscription_status` VARCHAR NULL,
	`trial_ends_at` DATETIME NULL,
	`subscription_ends_at` DATETIME NULL,
	`max_domains` INTEGER NOT NULL,
	`max_api_keys` INTEGER NOT NULL,
	`auto_create_users` TINYINT NOT NULL,
	`created_at` DATETIME NOT NULL,
	`updated_at` DATETIME NOT NULL,
	CONSTRAINT `pk_tenants` PRIMARY KEY(`tenant_id`)
);
CREATE TABLE `buttons` (
	`id` INTEGER PRIMARY KEY AUTOINCREMENT,
	`button_id` VARCHAR NOT NULL UNIQUE,
	`tenant_id` VARCHAR NOT NULL,
	`name` VARCHAR NOT NULL,
	`description` TEXT NULL,
	`domain` VARCHAR NOT NULL,
	`system_prompt` TEXT NULL,
	`provider` VARCHAR NOT NULL,
	`model` VARCHAR NOT NULL,
	`api_key_id` VARCHAR NOT NULL,
	`status` VARCHAR NOT NULL,
	`auto_create_api_users` TINYINT NULL,
	`created_at` DATETIME NOT NULL,
	`updated_at` DATETIME NOT NULL
);
CREATE UNIQUE INDEX `buttons_button_id` ON `buttons` (`button_id`);
CREATE TABLE `api_keys` (
	`api_key_id` VARCHAR NOT NULL,
	`tenant_id` VARCHAR NOT NULL,
	`name` VARCHAR NOT NULL,
	`provider` VARCHAR NOT NULL,
	`api_key` VARCHAR NOT NULL,
	`is_default` TINYINT NOT NULL,
	`active` TINYINT NOT NULL,
	`created_at` DATETIME NOT NULL,
	`updated_at` DATETIME NOT NULL,
	CONSTRAINT `pk_api_keys` PRIMARY KEY(`api_key_id`)
);
CREATE TABLE `api_tokens` (
	`id` INTEGER PRIMARY KEY AUTOINCREMENT,
	`user_id` INTEGER NOT NULL,
	`tenant_id` VARCHAR NULL,
	`name` VARCHAR NOT NULL,
	`token` VARCHAR NOT NULL,
	`refresh_token` VARCHAR NOT NULL,
	`scopes` TEXT NULL,
	`last_used_at` DATETIME NULL,
	`expires_at` DATETIME NULL,
	`revoked` TINYINT NOT NULL DEFAULT 0,
	`created_at` DATETIME NOT NULL,
	`updated_at` DATETIME NOT NULL
);
CREATE TABLE `tenant_users` (
	`id` INTEGER PRIMARY KEY AUTOINCREMENT,
	`tenant_id` VARCHAR NOT NULL,
	`user_id` VARCHAR NOT NULL,
	`name` VARCHAR NOT NULL,
	`email` VARCHAR NULL,
	`quota` INTEGER NOT NULL,
	`active` TINYINT NOT NULL,
	`created_at` DATETIME NOT NULL,
	`updated_at` DATETIME NOT NULL
);
CREATE UNIQUE INDEX `tenant_users_user_id` ON `tenant_users` (`user_id`);
CREATE TABLE `domains` (
	`domain_id` VARCHAR NOT NULL,
	`tenant_id` VARCHAR NOT NULL,
	`domain` VARCHAR NOT NULL,
	`verified` TINYINT NOT NULL DEFAULT 0,
	`created_at` DATETIME NOT NULL,
	CONSTRAINT `pk_domains` PRIMARY KEY(`domain_id`)
);
CREATE UNIQUE INDEX `domains_tenant_id_domain` ON `domains` (`tenant_id`, `domain`);
CREATE TABLE `plans` (
	`id` INTEGER PRIMARY KEY AUTOINCREMENT,
	`name` VARCHAR NOT NULL,
	`code` VARCHAR NOT NULL UNIQUE,
	`price` DECIMAL NOT NULL,
	`requests_limit` INTEGER NOT NULL,
	`users_limit` INTEGER NOT NULL,
	`features` TEXT NULL,
	`created_at` DATETIME NOT NULL,
	`updated_at` DATETIME NOT NULL
);
CREATE UNIQUE INDEX `plans_code` ON `plans` (`code`);
CREATE TABLE `usage_logs` (
	`id` INTEGER PRIMARY KEY AUTOINCREMENT,
	`usage_id` VARCHAR NOT NULL UNIQUE,
	`tenant_id` VARCHAR NOT NULL,
	`user_id` INTEGER NULL,
	`external_id` VARCHAR NULL,
	`button_id` VARCHAR NULL,
	`provider` VARCHAR NOT NULL,
	`model` VARCHAR NOT NULL,
	`tokens` INTEGER NOT NULL,
	`cost` DECIMAL NULL,
	`has_image` TINYINT NULL,
	`status` VARCHAR NOT NULL,
	`created_at` DATETIME NOT NULL,
	`updated_at` DATETIME NOT NULL
);
CREATE UNIQUE INDEX `usage_logs_usage_id` ON `usage_logs` (`usage_id`);
CREATE TABLE `user_quotas` (
	`tenant_id` VARCHAR NOT NULL,
	`external_id` VARCHAR NOT NULL,
	`total_quota` INTEGER NOT NULL,
	`created_at` DATETIME NOT NULL,
	CONSTRAINT `pk_user_quotas` PRIMARY KEY(`tenant_id`, `external_id`)
);
CREATE TABLE IF NOT EXISTS "api_users" (
	`user_id` VARCHAR NOT NULL,
	`tenant_id` VARCHAR NOT NULL,
	`external_id` VARCHAR NULL,
	`name` VARCHAR NULL,
	`email` VARCHAR NULL,
	`quota` INTEGER NULL,
	`daily_quota` INTEGER NOT NULL DEFAULT 10000,
	`active` TINYINT NOT NULL DEFAULT 1,
	`created_at` DATETIME NOT NULL,
	`updated_at` DATETIME NOT NULL,
	`last_activity` DATETIME NULL,
	CONSTRAINT `pk_api_users_new` PRIMARY KEY(`user_id`)
);
