<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantModel extends Model
{
    protected $table = 'tenants';
    protected $primaryKey = 'tenant_id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'tenant_id',
        'name',
        'email',
        'quota',
        'plan_code',
        'active',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'tenant_id' => 'required|min_length[3]|is_unique[tenants.tenant_id,tenant_id,{tenant_id}]',
        'name' => 'required|min_length[3]',
        'email' => 'required|valid_email',
        'plan_code' => 'required'
    ];

    /**
     * Get total number of API requests across all tenants
     */
    public function getTotalRequests()
    {
        $builder = $this->db->table('usage_logs');
        return $builder->countAllResults();
    }

    /**
     * Get all tenants with their details including plan info and usage
     */
    public function getAllTenantsWithDetails()
    {
        $builder = $this->db->table('tenants t');
        $builder->select('t.*, p.name as plan_name, p.price, COUNT(u.id) as request_count');
        $builder->join('plans p', 'p.code = t.plan_code', 'left');
        $builder->join('usage_logs u', 'u.tenant_id = t.tenant_id', 'left');
        $builder->groupBy('t.tenant_id');
        $builder->orderBy('t.created_at', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get a specific tenant with all its details
     */
    public function getTenantWithDetails($tenantId)
    {
        $builder = $this->db->table('tenants t');
        $builder->select('t.*, p.name as plan_name, p.price, COUNT(u.id) as request_count');
        $builder->join('plans p', 'p.code = t.plan_code', 'left');
        $builder->join('usage_logs u', 'u.tenant_id = t.tenant_id', 'left');
        $builder->where('t.tenant_id', $tenantId);
        $builder->groupBy('t.tenant_id');
        
        return $builder->get()->getRowArray();
    }

    /**
     * Get recent tenants with their plan details
     */
    public function getRecentTenants($limit = 5)
    {
        $builder = $this->db->table('tenants t');
        $builder->select('t.*, p.name as plan_name');
        $builder->join('plans p', 'p.code = t.plan_code', 'left');
        $builder->orderBy('t.created_at', 'DESC');
        $builder->limit($limit);
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get total number of users across all tenants
     */
    public function getTotalUsers()
    {
        $builder = $this->db->table('users');
        return $builder->countAllResults();
    }

    /**
     * Create a new tenant
     */
    public function createTenant($data)
    {
        // Set default values
        $data['active'] = 1;
        $data['subscription_status'] = 'trial';
        $data['trial_ends_at'] = date('Y-m-d H:i:s', strtotime('+14 days'));
        
        return $this->insert($data);
    }

    /**
     * Update tenant subscription
     */
    public function updateSubscription($tenantId, $planCode, $status = 'active')
    {
        $data = [
            'plan_code' => $planCode,
            'subscription_status' => $status,
            'trial_ends_at' => null
        ];

        if ($status === 'active') {
            $data['subscription_ends_at'] = date('Y-m-d H:i:s', strtotime('+1 month'));
        }

        return $this->update($tenantId, $data);
    }
}
