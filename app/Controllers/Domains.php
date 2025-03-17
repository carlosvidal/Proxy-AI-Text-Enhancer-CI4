<?php

namespace App\Controllers;

use App\Models\DomainsModel;
use App\Models\TenantsModel;

class Domains extends BaseController
{
    protected $domainsModel;
    protected $tenantsModel;
    
    public function __construct()
    {
        $this->domainsModel = new DomainsModel();
        $this->tenantsModel = new TenantsModel();
    }
    
    /**
     * Mostrar lista de dominios del tenant
     */
    public function index()
    {
        $tenantId = session('tenant_id');
        
        $data = [
            'title' => 'Dominios',
            'domains' => $this->tenantsModel->getDomains($tenantId),
            'tenant' => $this->tenantsModel->find($tenantId)
        ];
        
        return view('shared/domains/index', $data);
    }
    
    /**
     * Mostrar formulario para agregar dominio
     */
    public function create()
    {
        $tenantId = session('tenant_id');
        $tenant = $this->tenantsModel->find($tenantId);
        
        // Verificar si ha alcanzado el límite de dominios
        if ($this->domainsModel->hasReachedDomainLimit($tenantId)) {
            return redirect()->to('/domains')->with('error', 'Has alcanzado el límite de dominios permitidos para tu plan');
        }
        
        $data = [
            'title' => 'Agregar Dominio',
            'tenant' => $tenant
        ];
        
        return view('shared/domains/create', $data);
    }
    
    /**
     * Guardar nuevo dominio
     */
    public function store()
    {
        $tenantId = session('tenant_id');
        
        // Verificar si ha alcanzado el límite de dominios
        if ($this->domainsModel->hasReachedDomainLimit($tenantId)) {
            return redirect()->to('/domains')->with('error', 'Has alcanzado el límite de dominios permitidos para tu plan');
        }
        
        // Validar dominio
        $rules = [
            'domain' => 'required|min_length[4]|max_length[255]'
        ];
        
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('validation_error', $this->validator->getErrors());
        }
        
        // Preparar datos
        $domain = $this->request->getPost('domain');
        // Normalizar dominio (eliminar http://, https://, www.)
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        
        // Generar ID único
        $domainId = $this->domainsModel->generateDomainId();
        
        // Guardar dominio
        $data = [
            'domain_id' => $domainId,
            'tenant_id' => $tenantId,
            'domain' => $domain,
            'verified' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            if ($this->domainsModel->insert($data) === false) {
                $errors = $this->domainsModel->errors();
                if (!empty($errors)) {
                    return redirect()->back()->withInput()->with('validation_error', $errors);
                }
                return redirect()->back()->withInput()->with('error', 'Error al guardar el dominio');
            }
            
            return redirect()->to('/domains')->with('success', 'Dominio agregado correctamente. Pendiente de verificación.');
        } catch (\Exception $e) {
            log_message('error', '[Domain Creation] ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error al guardar el dominio: ' . $e->getMessage());
        }
    }
    
    /**
     * Verificar dominio
     */
    public function verify($domainId)
    {
        $domain = $this->domainsModel->find($domainId);
        
        if (!$domain || $domain['tenant_id'] !== session('tenant_id')) {
            return redirect()->to('/domains')->with('error', 'Dominio no encontrado');
        }
        
        // En un entorno real, aquí iría la lógica de verificación DNS
        // Por ahora, simplemente marcamos como verificado
        if ($this->domainsModel->verifyDomain($domainId)) {
            return redirect()->to('/domains')->with('success', 'Dominio verificado correctamente');
        }
        
        return redirect()->to('/domains')->with('error', 'Error al verificar el dominio');
    }
    
    /**
     * Eliminar dominio
     */
    public function delete($domainId)
    {
        $domain = $this->domainsModel->find($domainId);
        
        if (!$domain || $domain['tenant_id'] !== session('tenant_id')) {
            return redirect()->to('/domains')->with('error', 'Dominio no encontrado');
        }
        
        if ($this->domainsModel->delete($domainId)) {
            return redirect()->to('/domains')->with('success', 'Dominio eliminado correctamente');
        }
        
        return redirect()->to('/domains')->with('error', 'Error al eliminar el dominio');
    }
    
    /**
     * Método para Admin: gestionar dominios de un tenant
     */
    public function manageTenantDomains($tenantId)
    {
        // Verificar permisos de admin
        if (session('role') !== 'superadmin') {
            return redirect()->to('/admin')->with('error', 'No tienes permisos para realizar esta acción');
        }
        
        $data = [
            'title' => 'Gestionar Dominios',
            'domains' => $this->tenantsModel->getDomains($tenantId),
            'tenant' => $this->tenantsModel->find($tenantId)
        ];
        
        return view('admin/domains/manage', $data);
    }
    
    /**
     * Método para Admin: actualizar max_domains de un tenant
     */
    public function updateMaxDomains($tenantId)
    {
        // Verificar permisos de admin
        if (session('role') !== 'superadmin') {
            return redirect()->to('/admin')->with('error', 'No tienes permisos para realizar esta acción');
        }
        
        $rules = [
            'max_domains' => 'required|integer|greater_than[0]'
        ];
        
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        
        $maxDomains = $this->request->getPost('max_domains');
        
        if ($this->tenantsModel->update($tenantId, ['max_domains' => $maxDomains])) {
            return redirect()->to('/admin/tenants')->with('success', 'Límite de dominios actualizado correctamente');
        }
        
        return redirect()->back()->with('error', 'Error al actualizar el límite de dominios');
    }
}
