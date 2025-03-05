<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Users extends Controller
{
    /**
     * Ver el uso de un usuario específico
     */
    public function viewUsage($tenant_id, $user_id)
    {
        // Verificar sesión
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('auth/login');
        }

        $db = db_connect();

        // Obtener información del usuario
        $user_query = $db->table('tenant_users')
            ->where('tenant_id', $tenant_id)
            ->where('user_id', $user_id)
            ->get();

        if ($user_query->getNumRows() == 0) {
            return redirect()->to('tenants/users/' . $tenant_id)
                ->with('error', 'Usuario no encontrado');
        }

        $data['user'] = $user_query->getRowArray();
        $data['tenant_id'] = $tenant_id;

        // Obtener cuota actual
        $quota_query = $db->table('user_quotas')
            ->where('tenant_id', $tenant_id)
            ->where('user_id', $user_id)
            ->get();

        if ($quota_query->getNumRows() > 0) {
            $data['quota'] = $quota_query->getRowArray();
        } else {
            $data['quota'] = [
                'total_quota' => $data['user']['quota'],
                'reset_period' => 'monthly'
            ];
        }

        // Obtener historial de uso (últimos 30 días)
        $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));

        $usage_query = $db->table('usage_logs')
            ->where('tenant_id', $tenant_id)
            ->where('user_id', $user_id)
            ->where('usage_date >=', $thirty_days_ago)
            ->orderBy('usage_date', 'DESC')
            ->get();

        $data['usage_logs'] = $usage_query->getResultArray();

        // Calcular totales
        $data['total_used'] = 0;
        foreach ($data['usage_logs'] as $log) {
            $data['total_used'] += $log['tokens'];
        }

        $data['remaining'] = $data['quota']['total_quota'] - $data['total_used'];
        $data['percentage'] = $data['quota']['total_quota'] > 0
            ? min(100, ($data['total_used'] / $data['quota']['total_quota']) * 100)
            : 0;

        // Agrupar por fecha para gráfico
        $usage_by_date = [];
        foreach ($data['usage_logs'] as $log) {
            $date = date('Y-m-d', strtotime($log['usage_date']));
            if (!isset($usage_by_date[$date])) {
                $usage_by_date[$date] = 0;
            }
            $usage_by_date[$date] += $log['tokens'];
        }

        // Últimos 14 días para el gráfico
        $data['chart_labels'] = [];
        $data['chart_data'] = [];

        for ($i = 13; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $data['chart_labels'][] = $date;
            $data['chart_data'][] = isset($usage_by_date[$date]) ? $usage_by_date[$date] : 0;
        }

        $data['title'] = 'Uso de Tokens - Usuario ' . $user_id;

        return view('users/usage', $data);
    }

    /**
     * Restablecer el uso de un usuario
     */
    public function resetUsage($tenant_id, $user_id)
    {
        // Verificar sesión y rol admin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return redirect()->to('auth/login')
                ->with('error', 'Acceso denegado');
        }

        $db = db_connect();

        // Eliminar registros de uso del usuario
        $db->table('usage_logs')
            ->where('tenant_id', $tenant_id)
            ->where('user_id', $user_id)
            ->delete();

        return redirect()->to('users/usage/' . $tenant_id . '/' . $user_id)
            ->with('success', 'Uso de tokens restablecido correctamente');
    }
}
