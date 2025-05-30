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
            SELECT au.id,
                   au.external_id,
                   au.quota,
                   au.active,
                   COUNT(ul.id) as request_count,
                   COALESCE(SUM(ul.tokens), 0) as total_tokens,
                   MAX(ul.created_at) as last_used
            FROM api_users au
            LEFT JOIN usage_logs ul ON au.id = ul.user_id
            WHERE au.tenant_id = ?
            GROUP BY au.id, au.external_id, au.quota, au.active
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

        $db = db_connect();
        $logs = $db->table('usage_logs ul')
            ->select('ul.*, pl.system_prompt, pl.system_prompt_source, pl.messages, pl.response, b.name as button_name')
            ->join('prompt_logs pl', 'ul.id = pl.usage_log_id', 'left')
            ->join('buttons b', 'ul.button_id = b.button_id', 'left')
            ->where('ul.tenant_id', $tenant_id)
            ->orderBy('ul.created_at', 'DESC')
            ->get()
            ->getResult();

        return view('shared/usage/logs', [
            'title' => 'Usage Logs',
            'logs' => $logs
        ]);
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
            SELECT au.id,
                   au.external_id,
                   au.quota,
                   au.active,
                   COUNT(ul.id) as request_count,
                   COALESCE(SUM(ul.tokens), 0) as total_tokens,
                   MAX(ul.created_at) as last_used
            FROM api_users au
            LEFT JOIN usage_logs ul ON au.id = ul.user_id
            WHERE au.tenant_id = ? AND au.id = ?
            GROUP BY au.id, au.external_id, au.quota, au.active",
            [$tenant_id, $user_id]
        );

        $data['daily_stats'] = $query->getResult();

        return view('shared/usage/user', $data);
    }
}
