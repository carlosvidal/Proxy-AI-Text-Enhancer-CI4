<?php

namespace App\Models;

use CodeIgniter\Model;

class ApiKeyModel extends Model
{
    protected $table = 'api_keys';
    protected $primaryKey = 'api_key_id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'api_key_id',
        'tenant_id',
        'name',
        'provider',
        'api_key',
        'active',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'api_key_id' => 'required|regex_match[/^key-[0-9a-f]{8}-[0-9a-f]{8}$/]',
        'tenant_id' => 'required|regex_match[/^ten-[0-9a-f]{8}-[0-9a-f]{8}$/]',
        'name' => 'required|min_length[3]|max_length[255]',
        'provider' => 'required|in_list[openai,anthropic,cohere,mistral,deepseek,google]',
        'api_key' => 'required',
        'active' => 'permit_empty|in_list[0,1]'
    ];

    protected $beforeInsert = ['generateApiKeyId', 'encryptApiKey'];
    protected $beforeUpdate = ['encryptApiKey'];
    protected $afterFind = ['decryptApiKey'];

    protected function generateApiKeyId(array $data)
    {
        if (!isset($data['data']['api_key_id'])) {
            helper('hash');
            $data['data']['api_key_id'] = generate_hash_id('key');
        }
        return $data;
    }

    protected function encryptApiKey(array $data)
    {
        if (isset($data['data']['api_key']) && !empty($data['data']['api_key'])) {
            $encrypter = \Config\Services::encrypter();
            try {
                // Check if the key is already encrypted (base64 format)
                base64_decode($data['data']['api_key'], true);
                if (strpos($data['data']['api_key'], 'error:') === false) {
                    return $data;
                }
            } catch (\Exception $e) {
                // Not base64, encrypt it
                $data['data']['api_key'] = base64_encode($encrypter->encrypt($data['data']['api_key']));
            }
        }
        return $data;
    }

    protected function decryptApiKey(array $data)
    {
        $encrypter = \Config\Services::encrypter();

        // Handle single result
        if (isset($data['api_key'])) {
            try {
                $data['api_key'] = $encrypter->decrypt(base64_decode($data['api_key']));
            } catch (\Exception $e) {
                log_message('error', 'Failed to decrypt API key: ' . $e->getMessage());
            }
        }

        // Handle multiple results
        if (isset($data['data'])) {
            foreach ($data['data'] as &$row) {
                if (isset($row['api_key'])) {
                    try {
                        $row['api_key'] = $encrypter->decrypt(base64_decode($row['api_key']));
                    } catch (\Exception $e) {
                        log_message('error', 'Failed to decrypt API key: ' . $e->getMessage());
                    }
                }
            }
        }

        return $data;
    }
}
