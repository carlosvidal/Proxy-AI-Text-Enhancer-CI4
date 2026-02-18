<?php
namespace App\Controllers;

use App\Models\DomainsModel;
use App\Models\TenantsModel;
use CodeIgniter\Controller;

class AdminDomains extends Controller
{
    protected $domainsModel;
    protected $tenantsModel;

    public function __construct()
    {
        $this->domainsModel = new DomainsModel();
        $this->tenantsModel = new TenantsModel();
    }

    // Listar dominios de un tenant (admin)
    public function index($tenantId)
    {
        if (session('role') !== 'superadmin') {
            return redirect()->to('/admin')->with('error', 'No tienes permisos para realizar esta acción');
        }
        $data = [
            'title' => 'Dominios del Tenant',
            'domains' => $this->tenantsModel->getDomains($tenantId),
            'tenant' => $this->tenantsModel->find($tenantId)
        ];
        return view('admin/domains/manage', $data);
    }

    // Mostrar formulario para agregar dominio (admin)
    public function create($tenantId)
    {
        if (session('role') !== 'superadmin') {
            return redirect()->to('/admin')->with('error', 'No tienes permisos para realizar esta acción');
        }
        $tenant = $this->tenantsModel->find($tenantId);
        $data = [
            'title' => 'Agregar Dominio',
            'tenant' => $tenant
        ];
        return view('admin/domains/create', $data);
    }

    // Guardar nuevo dominio (admin)
    public function store($tenantId)
    {
        if (session('role') !== 'superadmin') {
            return redirect()->to('/admin')->with('error', 'No tienes permisos para realizar esta acción');
        }
        $rules = [
            'domain' => 'required|min_length[4]|max_length[255]'
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('validation_error', $this->validator->getErrors());
        }
        $domain = $this->request->getPost('domain');
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\\.#', '', $domain);
        $domainId = $this->domainsModel->generateDomainId();
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
            return redirect()->to('/admin/tenants/' . $tenantId . '/domains')->with('success', 'Dominio agregado correctamente. Pendiente de verificación.');
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint failed: domains.tenant_id, domains.domain') !== false) {
                return redirect()->back()->withInput()->with('error', 'Este dominio ya está registrado para este tenant.');
            }
            log_message('error', '[Admin Domain Creation] ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error al guardar el dominio: ' . $e->getMessage());
        }
    }

    // Verificar dominio (admin)
    public function verify($tenantId, $domainId)
    {
        if (session('role') !== 'superadmin') {
            return redirect()->to('/admin')->with('error', 'No tienes permisos para realizar esta acción');
        }
        $domain = $this->domainsModel->find($domainId);
        if (!$domain || $domain['tenant_id'] != $tenantId) {
            return redirect()->to('/admin/tenants/' . $tenantId . '/domains')->with('error', 'Dominio no encontrado');
        }
        if ($this->domainsModel->verifyDomain($domainId)) {
            return redirect()->to('/admin/tenants/' . $tenantId . '/domains')->with('success', 'Dominio verificado correctamente');
        }
        return redirect()->to('/admin/tenants/' . $tenantId . '/domains')->with('error', 'Error al verificar el dominio');
    }

    // Eliminar dominio (admin)
    public function delete($tenantId, $domainId)
    {
        if (session('role') !== 'superadmin') {
            return redirect()->to('/admin')->with('error', 'No tienes permisos para realizar esta acción');
        }
        $domain = $this->domainsModel->find($domainId);
        if (!$domain || $domain['tenant_id'] != $tenantId) {
            return redirect()->to('/admin/tenants/' . $tenantId . '/domains')->with('error', 'Dominio no encontrado');
        }
        if ($this->domainsModel->delete($domainId)) {
            return redirect()->to('/admin/tenants/' . $tenantId . '/domains')->with('success', 'Dominio eliminado correctamente');
        }
        return redirect()->to('/admin/tenants/' . $tenantId . '/domains')->with('error', 'Error al eliminar el dominio');
    }
}
