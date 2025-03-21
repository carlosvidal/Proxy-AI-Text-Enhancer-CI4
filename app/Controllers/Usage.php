<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UsageLogsModel;
use App\Models\TenantUsersModel;

/**
 * Usage Controller
 * 
 * Handles all usage statistics and monitoring for tenants.
 * This serves as the main dashboard for tenant users, showing:
 * - Overall token usage
 * - API user consumption
 * - Button usage statistics
 * - Detailed logs
 */
class Usage extends Controller
{
    protected $usageLogsModel;
    protected $tenantUsersModel;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->usageLogsModel = new UsageLogsModel();
        $this->tenantUsersModel = new TenantUsersModel();
    }

    /**
     * Main dashboard showing usage statistics
     */
    public function index()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $tenant_id = session()->get('tenant_id');
        $data['title'] = 'Usage Dashboard';

        // Get usage statistics for the current tenant
        $db = db_connect();

        // Get total tokens used
        $query = $db->query(
            "
            SELECT SUM(tokens) as total_tokens, COUNT(*) as total_requests
            FROM usage_logs 
            WHERE tenant_id = ?
            AND created_at >= date('now', '-30 days')",
            [$tenant_id]
        );
        $result = $query->getRow();

        $data['stats'] = [
            'total_tokens' => $result->total_tokens ?? 0,
            'total_requests' => $result->total_requests ?? 0
        ];

        // Get daily usage for the last 30 days
        $query = $db->query(
            "
            SELECT date(created_at) as usage_date, 
                   SUM(tokens) as daily_tokens,
                   COUNT(*) as daily_requests
            FROM usage_logs 
            WHERE tenant_id = ?
            AND created_at >= date('now', '-30 days')
            GROUP BY date(created_at)
            ORDER BY usage_date DESC",
            [$tenant_id]
        );
        $data['daily_stats'] = $query->getResult();

        // Get button usage statistics
        $query = $db->query(
            "
            SELECT b.name as button_name,
                   COUNT(*) as use_count,
                   SUM(u.tokens) as total_tokens
            FROM usage_logs u
            JOIN buttons b ON u.button_id = b.button_id
            WHERE u.tenant_id = ?
            AND u.created_at >= date('now', '-30 days')
            GROUP BY b.button_id, b.name
            ORDER BY use_count DESC",
            [$tenant_id]
        );
        $data['button_stats'] = $query->getResult();

        // Get API user statistics
        $query = $db->query(
            "
            SELECT tu.id,
                   tu.name,
                   tu.quota,
                   COUNT(ul.id) as request_count,
                   COALESCE(SUM(ul.tokens), 0) as total_tokens
            FROM tenant_users tu
            LEFT JOIN usage_logs ul ON tu.id = ul.user_id 
                AND ul.created_at >= date('now', '-30 days')
            WHERE tu.tenant_id = ?
            GROUP BY tu.id, tu.name, tu.quota
            ORDER BY total_tokens DESC",
            [$tenant_id]
        );
        $data['api_stats'] = $query->getResult();

        return view('shared/usage/index', $data);
    }

    /**
     * View detailed usage logs
     */
    public function logs()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $tenant_id = session()->get('tenant_id');
        $data['title'] = 'Usage Logs';

        // Get the latest 100 usage logs for the tenant
        $db = db_connect();
        $query = $db->query(
            "
            SELECT 
                ul.*,
                b.name as button_name,
                tu.name as user_name,
                COALESCE(pl.messages, '[]') as messages,
                COALESCE(pl.system_prompt, '') as system_prompt,
                COALESCE(pl.system_prompt_source, '') as system_prompt_source,
                COALESCE(pl.response, '') as response
            FROM usage_logs ul
            LEFT JOIN buttons b ON ul.button_id = b.button_id
            LEFT JOIN tenant_users tu ON ul.user_id = tu.id
            LEFT JOIN prompt_logs pl ON pl.usage_log_id = ul.id
            WHERE ul.tenant_id = ?
            ORDER BY ul.created_at DESC
            LIMIT 100",
            [$tenant_id]
        );

        $data['logs'] = $query->getResult();

        return view('shared/usage/logs', $data);
    }

    /**
     * View API user statistics
     */
    public function api()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $tenant_id = session()->get('tenant_id');
        $data['title'] = 'API Usage';

        // Get all API users with their usage statistics
        $db = db_connect();
        $query = $db->query(
            "
            SELECT tu.id,
                   tu.name,
                   tu.email,
                   tu.active,
                   tu.quota,
                   COUNT(ul.id) as request_count,
                   COALESCE(SUM(ul.tokens), 0) as total_tokens,
                   MAX(ul.created_at) as last_used
            FROM tenant_users tu
            LEFT JOIN usage_logs ul ON tu.id = ul.user_id
            WHERE tu.tenant_id = ?
            GROUP BY tu.id, tu.name, tu.email, tu.active, tu.quota
            ORDER BY total_tokens DESC",
            [$tenant_id]
        );

        $data['api_users'] = $query->getResult();

        return view('shared/usage/api', $data);
    }

    /**
     * View usage statistics for a specific API user
     * 
     * @param string $user_id The API user's identifier
     */
    public function userStats($user_id)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $tenant_id = session()->get('tenant_id');

        // Get API user information
        $user = $this->tenantUsersModel->where('tenant_id', $tenant_id)
            ->where('id', $user_id)
            ->first();

        if (!$user) {
            return redirect()->to('/usage/api')
                ->with('error', 'API user not found');
        }

        $data['title'] = 'API User Usage: ' . $user->name;
        $data['user'] = $user;

        // Get daily usage for this API user
        $db = db_connect();
        $query = $db->query(
            "
            SELECT date(created_at) as usage_date,
                   COUNT(*) as daily_requests,
                   SUM(tokens) as total_tokens
            FROM usage_logs
            WHERE tenant_id = ?
            AND user_id = ?
            AND created_at >= date('now', '-30 days')
            GROUP BY date(created_at)
            ORDER BY usage_date DESC",
            [$tenant_id, $user_id]
        );

        $data['daily_stats'] = $query->getResult();

        return view('shared/usage/user', $data);
    }
}
