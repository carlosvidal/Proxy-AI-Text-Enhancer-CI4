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
     * @param string $user_id ID externo del usuario (proporcionado por el tenant)
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

        // Verificar si la tabla existe antes de hacer consultas
        $tables = $db->listTables();
        if (in_array('user_quotas', $tables) && in_array('api_users', $tables)) {
            log_debug('QUOTA', 'Tablas user_quotas y api_users existen');

            // Primero verificar si hay una cuota definida en api_users
            $builder = $db->table('api_users');
            $builder->where('tenant_id', $tenant_id);
            $builder->where('external_id', $user_id);  // user_id from tenant is our external_id
            $query = $builder->get();

            $tenant_quota = 0;
            if ($query->getNumRows() > 0) {
                $row = $query->getRowArray();
                $tenant_quota = $row['quota'] ?? 0;
                log_debug('QUOTA', 'Cuota encontrada en api_users', ['quota' => $tenant_quota]);
            }

            // Ahora verificar si existe el usuario en user_quotas
            $builder = $db->table('user_quotas');
            $builder->where('tenant_id', $tenant_id);
            $builder->where('external_id', $user_id);  // user_id from tenant is our external_id
            $query = $builder->get();

            if ($query->getNumRows() == 0) {
                log_info('QUOTA', 'Usuario no encontrado en user_quotas, creando registro');

                // Si no existe, crear registro con cuota del tenant
                $data = [
                    'tenant_id' => $tenant_id,
                    'external_id' => $user_id,  // user_id from tenant is our external_id
                    'total_quota' => $tenant_quota > 0 ? $tenant_quota : env('DEFAULT_QUOTA', 100000),
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $insert_result = $db->table('user_quotas')->insert($data);
                log_debug('QUOTA', 'Resultado de inserción', [
                    'success' => $insert_result,
                    'data' => $data
                ]);

                $total_quota = $data['total_quota'];
                $used_quota = 0;
            } else {
                log_info('QUOTA', 'Usuario encontrado en user_quotas');

                // Si existe, obtener los valores
                $row = $query->getRowArray();
                $total_quota = $row['total_quota'];

                // Si la cuota en user_quotas no coincide con api_users, actualizar
                if ($tenant_quota > 0 && $total_quota != $tenant_quota) {
                    log_info('QUOTA', 'Sincronizando cuota con api_users', [
                        'old_quota' => $total_quota,
                        'new_quota' => $tenant_quota
                    ]);

                    $db->table('user_quotas')
                        ->where('tenant_id', $tenant_id)
                        ->where('external_id', $user_id)  // user_id from tenant is our external_id
                        ->update(['total_quota' => $tenant_quota]);

                    $total_quota = $tenant_quota;
                }

                // Obtener uso en el período actual
                $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));

                $builder = $db->table('usage_logs');
                $builder->selectSum('tokens', 'used_tokens');
                $builder->where('tenant_id', $tenant_id);
                $builder->where('external_id', $user_id);  // user_id from tenant is our external_id
                $builder->where('usage_date >=', $thirty_days_ago);
                $usage_query = $builder->get();

                $usage_row = $usage_query->getRow();
                $used_quota = $usage_row->used_tokens ?: 0;
            }

            $result = [
                'total' => $total_quota,
                'used' => $used_quota,
                'remaining' => $total_quota - $used_quota
            ];

            log_info('QUOTA', 'Resultado de cuota', $result);
            return $result;
        } else {
            // Si no hay tablas, devolver valores por defecto
            log_info('QUOTA', 'Tablas no existen. Usando valores por defecto.');

            $result = [
                'total' => env('DEFAULT_QUOTA', 100000),
                'used' => 0,
                'remaining' => env('DEFAULT_QUOTA', 100000)
            ];

            log_info('QUOTA', 'Cuota por defecto', $result);
            return $result;
        }
    }

    /**
     * Registra el uso de la API
     * 
     * @param string $tenant_id ID del tenant
     * @param string $user_id ID externo del usuario (proporcionado por el tenant)
     * @param string $provider Proveedor LLM
     * @param string $model Modelo utilizado
     * @param bool $has_image Si la petición incluía imágenes
     * @param int|null $token_count Cantidad real de tokens usados (si está disponible)
     * @return bool Éxito o fracaso
     */
    public function record_usage($tenant_id, $user_id, $provider, $model, $has_image, $token_count = null)
    {
        // Si no hay tenant o usuario, no registramos
        if (empty($tenant_id) || empty($user_id)) {
            $this->_log_usage($tenant_id, $user_id, $provider, $model, $has_image, $token_count);
            return FALSE;
        }

        // Sanitizar inputs
        $db = db_connect();
        $tenant_id = $db->escapeString($tenant_id);
        $user_id = $db->escapeString($user_id);
        $provider = $db->escapeString($provider);
        $model = $db->escapeString($model);

        // Si tenemos conteo de tokens real, usamos ese valor
        $tokens = $token_count;

        // Si no tenemos conteo real, estimamos (mantenemos la lógica anterior como respaldo)
        if ($tokens === null) {
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

            $tokens = $base_tokens * $token_multiplier * $model_multiplier;

            log_info('USAGE', 'Usando estimación de tokens', [
                'estimated_tokens' => $tokens,
                'provider' => $provider,
                'model' => $model
            ]);
        } else {
            log_info('USAGE', 'Usando conteo real de tokens', [
                'actual_tokens' => $tokens,
                'provider' => $provider,
                'model' => $model
            ]);
        }

        // Verificar si la tabla existe antes de hacer consultas
        $tables = $db->listTables();
        if (in_array('usage_logs', $tables)) {
            // Insertar registro de uso
            $data = [
                'tenant_id' => $tenant_id,
                'external_id' => $user_id,  // user_id from tenant is our external_id
                'provider' => $provider,
                'model' => $model,
                'has_image' => $has_image ? 1 : 0,
                'tokens' => $tokens,
                'usage_date' => date('Y-m-d H:i:s')
            ];

            return $db->table('usage_logs')->insert($data);
        } else {
            // Si no hay tabla de uso, solo registrar en log
            $this->_log_usage($tenant_id, $user_id, $provider, $model, $has_image, $tokens);
            return FALSE;
        }
    }

    /**
     * Registra el uso en un archivo de log
     */
    private function _log_usage($tenant_id, $user_id, $provider, $model, $has_image, $tokens = null)
    {
        $log_message = sprintf(
            "[%s] Usage: Tenant=%s, User=%s, Provider=%s, Model=%s, HasImage=%s, Tokens=%s",
            date('Y-m-d H:i:s'),
            $tenant_id,
            $user_id,
            $provider,
            $model,
            $has_image ? 'true' : 'false',
            $tokens !== null ? $tokens : 'unknown'
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
