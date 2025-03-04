<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\LlmProxyModel;

class Usage extends Controller
{
    protected $llm_proxy_model;

    public function __construct()
    {
        helper(['url', 'form', 'logger']);
        $this->llm_proxy_model = new LlmProxyModel();
    }

    /**
     * Página principal de estadísticas
     */
    public function index()
    {
        $data['title'] = 'LLM Proxy Usage Statistics';
        $db = db_connect();

        // Comprobar si las tablas existen
        $tables = $db->listTables();
        $data['tables_exist'] = [
            'user_quotas' => in_array('user_quotas', $tables),
            'usage_logs' => in_array('usage_logs', $tables),
            'llm_cache' => in_array('llm_cache', $tables)
        ];

        // Obtener estadísticas generales
        $data['stats'] = $this->_get_general_stats();

        // Obtener datos para gráficos
        $data['charts_data'] = $this->_get_charts_data();

        // Mostrar vista
        return view('usage/index', $data);
    }

    /**
     * Ver registros de uso detallados
     */
    public function logs($page = 0)
    {
        $data['title'] = 'LLM Proxy Usage Logs';
        $db = db_connect();

        // Comprobar si la tabla existe
        $tables = $db->listTables();
        if (!in_array('usage_logs', $tables)) {
            $data['error'] = 'The usage_logs table does not exist. Please run migrations first.';
            return view('usage/error', $data);
        }

        // Configuración de paginación
        $per_page = 20;
        $offset = $page * $per_page;

        // Obtener total de registros
        $builder = $db->table('usage_logs');
        $total_rows = $builder->countAllResults();
        $data['total_pages'] = ceil($total_rows / $per_page);
        $data['current_page'] = $page;

        // Obtener registros para esta página
        $builder = $db->table('usage_logs');
        $builder->orderBy('usage_date', 'DESC');
        $builder->limit($per_page, $offset);
        $data['logs'] = $builder->get()->getResult();

        // Mostrar vista
        return view('usage/logs', $data);
    }

    /**
     * Ver cuotas de usuarios
     */
    public function quotas()
    {
        $data['title'] = 'LLM Proxy User Quotas';
        $db = db_connect();

        // Comprobar si las tablas existen
        $tables = $db->listTables();
        if (!in_array('user_quotas', $tables) || !in_array('usage_logs', $tables)) {
            $data['error'] = 'The user_quotas or usage_logs table does not exist. Please run migrations first.';
            return view('usage/error', $data);
        }

        // Obtener registros de cuotas con uso actual
        $query = $db->query("
            SELECT 
                q.tenant_id,
                q.user_id,
                q.total_quota,
                q.reset_period,
                COALESCE(SUM(u.tokens), 0) as used_tokens,
                q.total_quota - COALESCE(SUM(u.tokens), 0) as remaining_quota
            FROM 
                user_quotas q
            LEFT JOIN 
                usage_logs u ON q.tenant_id = u.tenant_id AND q.user_id = u.user_id AND 
                u.usage_date >= datetime('now', '-30 days')
            GROUP BY 
                q.tenant_id, q.user_id
            ORDER BY
                q.tenant_id, q.user_id
        ");

        $data['quotas'] = $query->getResult();

        // Mostrar vista
        return view('usage/quotas', $data);
    }

    /**
     * Estadísticas por proveedor LLM
     */
    public function providers()
    {
        $data['title'] = 'LLM Proxy Provider Statistics';
        $db = db_connect();

        // Comprobar si la tabla existe
        $tables = $db->listTables();
        if (!in_array('usage_logs', $tables)) {
            $data['error'] = 'The usage_logs table does not exist. Please run migrations first.';
            return view('usage/error', $data);
        }

        // Obtener estadísticas por proveedor
        $query = $db->query("
            SELECT 
                provider,
                COUNT(*) as request_count,
                SUM(tokens) as total_tokens,
                COUNT(DISTINCT tenant_id) as tenant_count,
                COUNT(DISTINCT user_id) as user_count,
                SUM(CASE WHEN has_image = 1 THEN 1 ELSE 0 END) as image_requests
            FROM 
                usage_logs
            GROUP BY 
                provider
            ORDER BY 
                request_count DESC
        ");

        $data['provider_stats'] = $query->getResult();

        // Obtener estadísticas por modelo
        $query = $db->query("
            SELECT 
                provider,
                model,
                COUNT(*) as request_count,
                SUM(tokens) as total_tokens
            FROM 
                usage_logs
            GROUP BY 
                provider, model
            ORDER BY 
                provider, request_count DESC
        ");

        $data['model_stats'] = $query->getResult();

        // Mostrar vista
        return view('usage/providers', $data);
    }

    /**
     * Estadísticas de caché
     */
    public function cache()
    {
        $data['title'] = 'LLM Proxy Cache Statistics';
        $db = db_connect();

        // Comprobar si la tabla existe
        $tables = $db->listTables();
        if (!in_array('llm_cache', $tables)) {
            $data['error'] = 'The llm_cache table does not exist. Please run migrations first.';
            return view('usage/error', $data);
        }

        // Obtener estadísticas de caché
        $builder = $db->table('llm_cache');
        $data['cache_size'] = $builder->countAllResults();

        // Obtener estadísticas por proveedor
        $query = $db->query("
            SELECT 
                provider,
                COUNT(*) as entry_count,
                AVG(LENGTH(response)) as avg_response_size
            FROM 
                llm_cache
            GROUP BY 
                provider
            ORDER BY 
                entry_count DESC
        ");

        $data['provider_stats'] = $query->getResult();

        // Obtener entradas más recientes
        $builder = $db->table('llm_cache');
        $builder->orderBy('created_at', 'DESC');
        $builder->limit(10);
        $data['recent_entries'] = $builder->get()->getResult();

        // Mostrar vista
        return view('usage/cache', $data);
    }

    /**
     * Obtiene estadísticas generales para el dashboard
     */
    private function _get_general_stats()
    {
        $stats = [];
        $db = db_connect();
        $tables = $db->listTables();

        if (in_array('usage_logs', $tables)) {
            // Total de peticiones
            $builder = $db->table('usage_logs');
            $stats['total_requests'] = $builder->countAllResults();

            // Peticiones en las últimas 24 horas
            $builder = $db->table('usage_logs');
            $builder->where('usage_date >=', date('Y-m-d H:i:s', strtotime('-24 hours')));
            $stats['recent_requests'] = $builder->countAllResults();

            // Tokens totales consumidos
            $query = $db->query("SELECT SUM(tokens) as total FROM usage_logs");
            $stats['total_tokens'] = isset($query->getRow()->total) ? $query->getRow()->total : '0';

            // Número de tenants y usuarios únicos
            $query = $db->query("SELECT COUNT(DISTINCT tenant_id) as tenants, COUNT(DISTINCT user_id) as users FROM usage_logs");
            $row = $query->getRow();
            $stats['unique_tenants'] = isset($row->tenants) ? $row->tenants : 0;
            $stats['unique_users'] = isset($row->users) ? $row->users : 0;

            // Peticiones con imágenes
            $builder = $db->table('usage_logs');
            $builder->where('has_image', 1);
            $stats['image_requests'] = $builder->countAllResults();
        } else {
            // Valores por defecto si no existe la tabla
            $stats = [
                'total_requests' => 0,
                'recent_requests' => 0,
                'total_tokens' => 0,
                'unique_tenants' => 0,
                'unique_users' => 0,
                'image_requests' => 0
            ];
        }

        return $stats;
    }

    /**
     * Obtiene datos para los gráficos del dashboard
     */
    private function _get_charts_data()
    {
        $charts_data = [];
        $db = db_connect();
        $tables = $db->listTables();

        if (in_array('usage_logs', $tables)) {
            // Uso por día (últimos 30 días)
            $query = $db->query("
                SELECT 
                    date(usage_date) as date,
                    COUNT(*) as requests,
                    SUM(tokens) as tokens
                FROM 
                    usage_logs
                WHERE 
                    usage_date >= datetime('now', '-30 days')
                GROUP BY 
                    date(usage_date)
                ORDER BY 
                    date ASC
            ");

            $charts_data['usage_by_date'] = $query->getResult();

            // Uso por proveedor
            $query = $db->query("
                SELECT 
                    provider,
                    COUNT(*) as count
                FROM 
                    usage_logs
                GROUP BY 
                    provider
                ORDER BY 
                    count DESC
            ");

            $charts_data['usage_by_provider'] = $query->getResult();

            // Uso por modelo
            $query = $db->query("
                SELECT 
                    model,
                    COUNT(*) as count
                FROM 
                    usage_logs
                GROUP BY 
                    model
                ORDER BY 
                    count DESC
                LIMIT 
                    5
            ");

            $charts_data['usage_by_model'] = $query->getResult();
        } else {
            // Valores por defecto si no existe la tabla
            $charts_data = [
                'usage_by_date' => [],
                'usage_by_provider' => [],
                'usage_by_model' => []
            ];
        }

        return $charts_data;
    }
}
