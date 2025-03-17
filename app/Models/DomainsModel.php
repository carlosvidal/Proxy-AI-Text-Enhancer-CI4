<?php

namespace App\Models;

use CodeIgniter\Model;

class DomainsModel extends Model
{
    protected $table = 'domains';
    protected $primaryKey = 'domain_id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'domain_id', 
        'tenant_id', 
        'domain', 
        'verified', 
        'created_at'
    ];
    protected $useTimestamps = false;
    protected $createdField = 'created_at';
    
    protected $validationRules = [
        'domain_id' => 'required|alpha_dash|min_length[8]|max_length[32]',
        'tenant_id' => 'required|alpha_dash|min_length[8]|max_length[32]',
        'domain' => 'required|min_length[4]|max_length[255]|is_unique[domains.domain,tenant_id,{tenant_id}]',
    ];
    
    protected $validationMessages = [
        'domain' => [
            'required' => 'El dominio es obligatorio',
            'min_length' => 'El dominio debe tener al menos 4 caracteres',
            'max_length' => 'El dominio no puede exceder los 255 caracteres',
            'is_unique' => 'Este dominio ya está registrado para tu cuenta'
        ]
    ];
    
    /**
     * Generar un ID de dominio único
     * 
     * @return string ID del dominio
     */
    public function generateDomainId()
    {
        helper('hash');
        return generate_hash_id('dom');
    }
    
    /**
     * Verificar si un tenant ha alcanzado su límite de dominios
     * 
     * @param string $tenantId ID del tenant
     * @return bool True si ha alcanzado el límite, false en caso contrario
     */
    public function hasReachedDomainLimit($tenantId)
    {
        $tenantsModel = new TenantsModel();
        $tenant = $tenantsModel->find($tenantId);
        
        if (!$tenant) {
            return true;
        }
        
        $domainCount = $this->where('tenant_id', $tenantId)->countAllResults();
        return $domainCount >= $tenant['max_domains'];
    }
    
    /**
     * Obtener dominios verificados para un tenant
     * 
     * @param string $tenantId ID del tenant
     * @return array Lista de dominios verificados
     */
    public function getVerifiedDomains($tenantId)
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('verified', 1)
                    ->findAll();
    }
    
    /**
     * Verificar un dominio
     * 
     * @param string $domainId ID del dominio
     * @return bool True si se verificó correctamente, false en caso contrario
     */
    public function verifyDomain($domainId)
    {
        return $this->update($domainId, ['verified' => 1]);
    }
}
