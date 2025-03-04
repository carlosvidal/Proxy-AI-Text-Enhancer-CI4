<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration para crear las tablas necesarias para el LLM Proxy usando SQLite
 */
class CreateLlmProxyTables extends Migration
{
    /**
     * Crea las tablas necesarias
     *
     * @return void
     */
    public function up()
    {
        // Crear tabla para cuotas de usuarios
        // En SQLite usamos INTEGER para autoincrement en lugar de INT
        $this->db->query("
            CREATE TABLE IF NOT EXISTS user_quotas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id VARCHAR(50) NOT NULL,
                user_id VARCHAR(50) NOT NULL,
                total_quota INTEGER NOT NULL,
                reset_period VARCHAR(10) DEFAULT 'monthly' CHECK(reset_period IN ('daily','weekly','monthly')),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (tenant_id, user_id)
            )
        ");

        // Crear tabla para registros de uso
        $this->db->query("
            CREATE TABLE IF NOT EXISTS usage_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id VARCHAR(50) NOT NULL,
                user_id VARCHAR(50) NOT NULL,
                provider VARCHAR(20) NOT NULL,
                model VARCHAR(50) NOT NULL,
                has_image INTEGER DEFAULT 0,
                tokens INTEGER NOT NULL,
                usage_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Crear índices para mejorar rendimiento
        $this->db->query("CREATE INDEX idx_tenant_user ON usage_logs(tenant_id, user_id)");
        $this->db->query("CREATE INDEX idx_usage_date ON usage_logs(usage_date)");

        // Crear tabla para caché de respuestas (opcional, pero útil)
        $this->db->query("
            CREATE TABLE IF NOT EXISTS llm_cache (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                prompt_hash VARCHAR(64) NOT NULL,
                provider VARCHAR(20) NOT NULL,
                model VARCHAR(50) NOT NULL,
                response TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (prompt_hash, provider, model)
            )
        ");

        // Crear índice para búsquedas rápidas en caché
        $this->db->query("CREATE INDEX idx_cache_lookup ON llm_cache(prompt_hash, provider, model)");

        // Trigger para actualizar updated_at en user_quotas (SQLite no tiene ON UPDATE CURRENT_TIMESTAMP)
        $this->db->query("
            CREATE TRIGGER update_user_quotas_timestamp 
            AFTER UPDATE ON user_quotas
            FOR EACH ROW
            BEGIN
                UPDATE user_quotas SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
            END
        ");
    }

    /**
     * Elimina las tablas creadas
     *
     * @return void
     */
    public function down()
    {
        // Eliminar triggers
        $this->db->query("DROP TRIGGER IF EXISTS update_user_quotas_timestamp");

        // Eliminar tablas
        $this->db->query("DROP TABLE IF EXISTS llm_cache");
        $this->db->query("DROP TABLE IF EXISTS usage_logs");
        $this->db->query("DROP TABLE IF EXISTS user_quotas");
    }
}
