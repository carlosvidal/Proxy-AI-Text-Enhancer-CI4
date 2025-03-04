<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modelo de LLM Proxy para CodeIgniter 4
 * 
 * Este modelo maneja las operaciones de base de datos para el proxy LLM
 */
class LlmProxyModel extends Model
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        helper('logger');
    }

    /**
     * Verifica la cuota disponible para un tenant/usuario (Versión SQLite)
     * 
     * @param string $tenant_id ID del tenant
     * @param string $user_id ID del usuario
     * @return array Información sobre la cuota
     */
    public function check_quota($tenant_id, $user_id)
    {
        log_debug('QUOTA', 'Verificando cuota', [
            'tenant_id' => $tenant_id,
            'user_id' => $user_id
        ]);

        // Sanitizar inputs para prevenir SQL injection
        $db = db_connect();
        $tenant_id = $db->escapeString($tenant_id);
        $user_id = $db->escapeString($user_id);

        // Cargar configuración 
        $default_quota = env('DEFAULT_QUOTA', 100);

        // Verificar si la tabla existe antes de hacer consultas
        $tables = $db->listTables();
        if (in_array('user_quotas', $tables)) {
            log_debug('QUOTA', 'Tabla user_quotas existe');

            // Consulta para verificar si existe el usuario
            $builder = $db->table('user_quotas');
            $builder->where('tenant_id', $tenant_id);
            $builder->where('user_id', $user_id);
            $query = $builder->get();

            log_debug('QUOTA', 'Consulta SQL ejecutada', [
                'last_query' => $db->getLastQuery(),
                'num_rows' => $query->getNumRows()
            ]);

            if ($query->getNumRows() == 0) {
                log_info('QUOTA', 'Usuario no encontrado, creando registro con cuota por defecto');

                // Si no existe, crear registro con cuota por defecto
                $data = [
                    'tenant_id' => $tenant_id,
                    'user_id' => $user_id,
                    'total_quota' => $default_quota,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $insert_result = $db->table('user_quotas')->insert($data);
                log_debug('QUOTA', 'Resultado de inserción', [
                    'success' => $insert_result,
                    'last_query' => $db->getLastQuery()
                ]);

                $total_quota = $default_quota;
                $used_quota = 0;
            } else {
                log_info('QUOTA', 'Usuario encontrado, obteniendo cuota');

                // Si existe, obtener los valores
                $row = $query->getRowArray();
                $total_quota = $row['total_quota'];

                // Obtener uso en el período actual (30 días)
                $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));

                $builder = $db->table('usage_logs');
                $builder->selectSum('tokens', 'used_tokens');
                $builder->where('tenant_id', $tenant_id);
                $builder->where('user_id', $user_id);
                $builder->where('usage_date >=', $thirty_days_ago);
                $usage_query = $builder->get();

                log_debug('QUOTA', 'Consulta de uso ejecutada', [
                    'last_query' => $db->getLastQuery()
                ]);

                $usage_row = $usage_query->getRow();
                $used_quota = $usage_row->used_tokens ?: 0;

                log_debug('QUOTA', 'Uso obtenido', [
                    'used_tokens' => $used_quota
                ]);
            }

            $result = [
                'total' => $total_quota,
                'used' => $used_quota,
                'remaining' => $total_quota - $used_quota
            ];

            log_info('QUOTA', 'Resultado de cuota', $result);
            return $result;
        } else {
            // Si no hay tabla de cuotas, devolver valores por defecto
            log_info('QUOTA', 'Tabla user_quotas no existe. Usando valores por defecto.');

            $result = [
                'total' => $default_quota,
                'used' => 0,
                'remaining' => $default_quota
            ];

            log_info('QUOTA', 'Cuota por defecto', $result);
            return $result;
        }
    }

    /**
     * Registra el uso de la API
     * 
     * @param string $tenant_id ID del tenant
     * @param string $user_id ID del usuario
     * @param string $provider Proveedor LLM
     * @param string $model Modelo utilizado
     * @param bool $has_image Si la petición incluía imágenes
     * @return bool Éxito o fracaso
     */
    public function record_usage($tenant_id, $user_id, $provider, $model, $has_image)
    {
        // Si no hay tenant o usuario, no registramos
        if (empty($tenant_id) || empty($user_id)) {
            $this->_log_usage($tenant_id, $user_id, $provider, $model, $has_image);
            return FALSE;
        }

        // Sanitizar inputs
        $db = db_connect();
        $tenant_id = $db->escapeString($tenant_id);
        $user_id = $db->escapeString($user_id);
        $provider = $db->escapeString($provider);
        $model = $db->escapeString($model);

        // Estimar tokens usados (esto es una aproximación)
        $base_tokens = 500; // Valor base
        $token_multiplier = $has_image ? 2 : 1; // Las imágenes consumen más tokens

        // Diferentes modelos tienen diferentes costos
        switch ($model) {
            case 'gpt-4-turbo':
            case 'claude-3-opus-20240229':
                $model_multiplier = 2.0;
                break;
            case 'gpt-3.5-turbo':
            case 'mistral-medium-latest':
                $model_multiplier = 0.5;
                break;
            default:
                $model_multiplier = 1.0;
        }

        $estimated_tokens = $base_tokens * $token_multiplier * $model_multiplier;

        // Verificar si la tabla existe antes de hacer consultas
        $tables = $db->listTables();
        if (in_array('usage_logs', $tables)) {
            // Insertar registro de uso
            $data = [
                'tenant_id' => $tenant_id,
                'user_id' => $user_id,
                'provider' => $provider,
                'model' => $model,
                'has_image' => $has_image ? 1 : 0,
                'tokens' => $estimated_tokens,
                'usage_date' => date('Y-m-d H:i:s')
            ];

            return $db->table('usage_logs')->insert($data);
        } else {
            // Si no hay tabla de uso, solo registrar en log
            $this->_log_usage($tenant_id, $user_id, $provider, $model, $has_image);
            return FALSE;
        }
    }

    /**
     * Registra el uso en un archivo de log
     */
    private function _log_usage($tenant_id, $user_id, $provider, $model, $has_image)
    {
        $log_message = sprintf(
            "[%s] Usage: Tenant=%s, User=%s, Provider=%s, Model=%s, HasImage=%s",
            date('Y-m-d H:i:s'),
            $tenant_id,
            $user_id,
            $provider,
            $model,
            $has_image ? 'true' : 'false'
        );

        log_message('info', $log_message);
    }

    /**
     * Implementación de caché para respuestas (opcional)
     * 
     * @param string $prompt_hash Hash del prompt
     * @param string $provider Proveedor LLM
     * @param string $model Modelo utilizado
     * @return string|null Respuesta cacheada o null si no existe
     */
    public function get_cached_response($prompt_hash, $provider, $model)
    {
        $db = db_connect();
        $tables = $db->listTables();
        if (!in_array('llm_cache', $tables)) {
            return NULL;
        }

        $builder = $db->table('llm_cache');
        $builder->where('prompt_hash', $prompt_hash);
        $builder->where('provider', $provider);
        $builder->where('model', $model);

        // En SQLite, verificamos la edad del registro manualmente
        $cache_ttl = env('CACHE_LIFETIME', 3600); // 1 hora por defecto
        $expire_time = date('Y-m-d H:i:s', time() - $cache_ttl);
        $builder->where('created_at >', $expire_time);

        $query = $builder->get();

        if ($query->getNumRows() > 0) {
            return $query->getRow()->response;
        }

        return NULL;
    }

    /**
     * Guarda una respuesta en caché (opcional)
     * 
     * @param string $prompt_hash Hash del prompt
     * @param string $provider Proveedor LLM
     * @param string $model Modelo utilizado
     * @param string $response Respuesta a guardar
     * @return bool Éxito o fracaso
     */
    public function cache_response($prompt_hash, $provider, $model, $response)
    {
        $db = db_connect();
        $tables = $db->listTables();
        if (!in_array('llm_cache', $tables)) {
            return FALSE;
        }

        // SQLite maneja REPLACE INTO de forma diferente, así que primero eliminamos
        $builder = $db->table('llm_cache');
        $builder->where('prompt_hash', $prompt_hash);
        $builder->where('provider', $provider);
        $builder->where('model', $model);
        $builder->delete();

        // Luego insertamos el nuevo registro
        $data = [
            'prompt_hash' => $prompt_hash,
            'provider' => $provider,
            'model' => $model,
            'response' => $response,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $db->table('llm_cache')->insert($data);
    }
}
