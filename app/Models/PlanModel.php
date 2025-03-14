<?php

namespace App\Models;

use CodeIgniter\Model;

class PlanModel extends Model
{
    protected $table = 'plans';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['name', 'code', 'price', 'requests_limit', 'users_limit', 'features'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation rules
    protected $validationRules = [
        'name' => 'required|min_length[3]|max_length[100]',
        'code' => 'required|min_length[3]|max_length[50]|is_unique[plans.code,id,{id}]',
        'price' => 'required|numeric',
        'requests_limit' => 'required|numeric',
        'users_limit' => 'required|numeric'
    ];

    public function getActivePlans()
    {
        return $this->findAll();
    }

    public function getPlanByCode($code)
    {
        return $this->where('code', $code)->first();
    }
}
