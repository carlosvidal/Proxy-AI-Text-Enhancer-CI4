<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ButtonsSeeder extends Seeder
{
    public function run()
    {
        $buttonsModel = new \App\Models\ButtonsModel();
        $db = \Config\Database::connect();

        // Get all tenants
        $tenants = $db->table('tenants')->get()->getResultArray();

        foreach ($tenants as $tenant) {
            // Default buttons for each tenant
            $buttons = [
                [
                    'tenant_id' => $tenant['tenant_id'],
                    'button_id' => bin2hex(random_bytes(8)),
                    'name' => 'Improve Writing',
                    'domain' => '*',
                    'provider' => 'openai',
                    'model' => 'gpt-3.5-turbo',
                    'system_prompt' => 'You are a professional editor. Your task is to improve the writing while maintaining the original meaning. Make it clear, concise, and professional.',
                    'active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                [
                    'tenant_id' => $tenant['tenant_id'],
                    'button_id' => bin2hex(random_bytes(8)),
                    'name' => 'Fix Grammar',
                    'domain' => '*',
                    'provider' => 'openai',
                    'model' => 'gpt-3.5-turbo',
                    'system_prompt' => 'You are a grammar expert. Your task is to fix any grammatical errors while preserving the original meaning and style.',
                    'active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                [
                    'tenant_id' => $tenant['tenant_id'],
                    'button_id' => bin2hex(random_bytes(8)),
                    'name' => 'Make Professional',
                    'domain' => '*',
                    'provider' => 'openai',
                    'model' => 'gpt-3.5-turbo',
                    'system_prompt' => 'You are a business writing expert. Your task is to make the text more professional and suitable for business communication while maintaining its core message.',
                    'active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ];

            // Insert buttons for this tenant using direct database insert
            foreach ($buttons as $button) {
                $db->table('buttons')->insert($button);
            }
        }
    }
}
