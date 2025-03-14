<?php

namespace App\Models;

use CodeIgniter\Model;

class UsageModel extends Model
{
    protected $table = 'usage_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'tenant_id',
        'user_id',
        'button_id',
        'input_length',
        'output_length',
        'status',
        'created_at'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get total number of requests across all tenants
     */
    public function getTotalRequests()
    {
        return $this->countAllResults();
    }

    /**
     * Get usage statistics by plan
     */
    public function getUsageByPlan()
    {
        $builder = $this->db->table('usage_logs ul');
        $builder->select('p.name as plan_name, COUNT(ul.id) as total_requests');
        $builder->join('tenants t', 't.id = ul.tenant_id');
        $builder->join('plans p', 'p.code = t.plan_code');
        $builder->groupBy('p.code');
        $builder->orderBy('total_requests', 'DESC');

        return $builder->get()->getResultArray();
    }

    /**
     * Get total requests for a specific plan
     */
    public function getRequestsByPlan($planCode)
    {
        $builder = $this->db->table('usage_logs ul');
        $builder->join('tenants t', 't.id = ul.tenant_id');
        $builder->where('t.plan_code', $planCode);

        return $builder->countAllResults();
    }

    /**
     * Get usage statistics for a specific tenant
     */
    public function getTenantUsage($tenantId)
    {
        $builder = $this->db->table('usage_logs');
        $builder->where('tenant_id', $tenantId);
        
        return [
            'total_requests' => $builder->countAllResults(),
            'recent_requests' => $builder->orderBy('created_at', 'DESC')
                                      ->limit(10)
                                      ->get()
                                      ->getResultArray()
        ];
    }

    /**
     * Log a new usage entry
     */
    public function logUsage($data)
    {
        return $this->insert([
            'tenant_id' => $data['tenant_id'],
            'user_id' => $data['user_id'],
            'button_id' => $data['button_id'],
            'input_length' => $data['input_length'] ?? 0,
            'output_length' => $data['output_length'] ?? 0,
            'status' => $data['status'] ?? 'success'
        ]);
    }

    /**
     * Get usage statistics for the current month
     */
    public function getCurrentMonthUsage($tenantId)
    {
        $builder = $this->db->table('usage_logs');
        $builder->where('tenant_id', $tenantId);
        $builder->where('MONTH(created_at)', date('m'));
        $builder->where('YEAR(created_at)', date('Y'));

        return $builder->countAllResults();
    }
}
