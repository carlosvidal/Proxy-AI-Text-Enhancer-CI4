<?php

namespace App\Models;

use CodeIgniter\Model;

class ButtonsModel extends Model
{
    protected $table      = 'buttons';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'tenant_id',
        'button_id',
        'name',
        'domain',
        'provider',
        'model',
        'api_key',
        'system_prompt',
        'active',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules    = [
        'tenant_id'     => 'required',
        'name'          => 'required|min_length[3]|max_length[255]',
        'domain'        => 'required|min_length[3]|max_length[255]',
        'provider'      => 'required',
        'model'         => 'required',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Override the insert method to encrypt API keys
     */
    public function insert($data = null, bool $returnID = true)
    {
        // Check if data contains an API key
        if (isset($data['api_key']) && !empty($data['api_key'])) {
            helper('api_key');
            $data['api_key'] = encrypt_api_key($data['api_key']);
        }

        return parent::insert($data, $returnID);
    }

    /**
     * Override the update method to handle API key encryption
     */
    public function update($id = null, $data = null): bool
    {
        // Check if data contains an API key and it's not empty
        if (isset($data['api_key']) && !empty($data['api_key'])) {
            // If it's the special string "delete", set it to null instead
            if ($data['api_key'] === 'delete') {
                $data['api_key'] = null;
            } else {
                // Otherwise, encrypt the new API key
                helper('api_key');
                $data['api_key'] = encrypt_api_key($data['api_key']);
            }
        } elseif (isset($data['api_key']) && empty($data['api_key'])) {
            // If an empty API key is provided, don't update it
            // This allows keeping the existing key
            unset($data['api_key']);
        }

        return parent::update($id, $data);
    }

    /**
     * Override the find method to handle API key decryption
     */
    public function find($id = null)
    {
        $result = parent::find($id);

        // If the API key exists, don't decrypt it in the basic find
        // We'll only decrypt when actually using it

        return $result;
    }

    /**
     * Get all buttons for a specific tenant
     * 
     * @param string $tenant_id Tenant ID
     * @return array Array of buttons
     */
    public function getButtonsByTenant($tenant_id)
    {
        return $this->where('tenant_id', $tenant_id)
            ->findAll();
    }

    /**
     * Get a button by domain and tenant_id
     * 
     * @param string $domain Domain to match
     * @param string $tenant_id Tenant ID
     * @return array|null Button data or null if not found
     */
    public function getButtonByDomain($domain, $tenant_id)
    {
        $button = $this->where('domain', $domain)
            ->where('tenant_id', $tenant_id)
            ->where('active', 1)
            ->first();

        // If a button was found and it has an API key, decrypt it for use
        if ($button && !empty($button['api_key'])) {
            helper('api_key');
            $button['api_key'] = decrypt_api_key($button['api_key']);
        }

        return $button;
    }

    /**
     * Check if a button with the same domain and tenant exists
     * 
     * @param string $domain Domain to check
     * @param string $tenant_id Tenant ID
     * @param int|null $button_id Button ID to exclude from check (for updates)
     * @return bool True if exists, false otherwise
     */
    public function domainExists($domain, $tenant_id, $button_id = null)
    {
        $builder = $this->where('domain', $domain)
            ->where('tenant_id', $tenant_id);

        if ($button_id !== null) {
            $builder->where('id !=', $button_id);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Generate a unique button_id
     * 
     * @return string Unique button_id
     */
    public function generateButtonId()
    {
        // Generate a random button_id
        $button_id = bin2hex(random_bytes(8)); // 16 character hex string

        // Check if it's unique
        while ($this->where('button_id', $button_id)->countAllResults() > 0) {
            $button_id = bin2hex(random_bytes(8));
        }

        return $button_id;
    }

    /**
     * Get a button by its button_id
     * 
     * @param string $button_id Button ID to find
     * @return array|null Button data or null if not found
     */
    public function getButtonByButtonId($button_id)
    {
        $button = $this->where('button_id', $button_id)
            ->where('active', 1)
            ->first();

        // If a button was found and it has an API key, decrypt it for use
        if ($button && !empty($button['api_key'])) {
            helper('api_key');
            $button['api_key'] = decrypt_api_key($button['api_key']);
        }

        return $button;
    }
}
