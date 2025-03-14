<?php

namespace App\Models;

use CodeIgniter\Model;

class UsageLogsModel extends Model
{
    protected $table = 'usage_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'tenant_id',
        'button_id',
        'api_user_id',
        'tokens',
        'cost',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    protected $validationRules = [
        'tenant_id' => 'required',
        'tokens' => 'required|integer',
        'cost' => 'required|decimal'
    ];

    /**
     * Get usage statistics for a tenant
     */
    public function getTenantStats($tenant_id, $days = 30)
    {
        $db = $this->db;
        
        $query = $db->query("
            SELECT SUM(tokens) as total_tokens, 
                   COUNT(*) as total_requests,
                   SUM(cost) as total_cost
            FROM usage_logs 
            WHERE tenant_id = ?
            AND created_at >= date('now', ? || ' days')",
            [$tenant_id, -$days]
        );
        
        return $query->getRow();
    }

    /**
     * Get daily usage for a tenant
     */
    public function getDailyUsage($tenant_id, $days = 30)
    {
        $db = $this->db;
        
        $query = $db->query("
            SELECT date(created_at) as usage_date,
                   SUM(tokens) as daily_tokens,
                   COUNT(*) as daily_requests,
                   SUM(cost) as daily_cost
            FROM usage_logs 
            WHERE tenant_id = ?
            AND created_at >= date('now', ? || ' days')
            GROUP BY date(created_at)
            ORDER BY usage_date DESC",
            [$tenant_id, -$days]
        );
        
        return $query->getResult();
    }

    /**
     * Get button usage statistics for a tenant
     */
    public function getButtonStats($tenant_id, $days = 30)
    {
        $db = $this->db;
        
        $query = $db->query("
            SELECT b.name as button_name,
                   COUNT(*) as use_count,
                   SUM(u.tokens) as total_tokens,
                   SUM(u.cost) as total_cost
            FROM usage_logs u
            JOIN buttons b ON u.button_id = b.id
            WHERE u.tenant_id = ?
            AND u.created_at >= date('now', ? || ' days')
            GROUP BY b.id, b.name
            ORDER BY use_count DESC",
            [$tenant_id, -$days]
        );
        
        return $query->getResult();
    }

    /**
     * Get API usage statistics for a tenant
     */
    public function getApiStats($tenant_id, $days = 30)
    {
        $db = $this->db;
        
        $query = $db->query("
            SELECT api_user_id,
                   COUNT(*) as request_count,
                   SUM(tokens) as total_tokens,
                   SUM(cost) as total_cost,
                   MAX(created_at) as last_used
            FROM usage_logs
            WHERE tenant_id = ?
            AND api_user_id IS NOT NULL
            AND created_at >= date('now', ? || ' days')
            GROUP BY api_user_id
            ORDER BY request_count DESC",
            [$tenant_id, -$days]
        );
        
        return $query->getResult();
    }
}
