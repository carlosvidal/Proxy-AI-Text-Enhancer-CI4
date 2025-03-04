<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Config\Services;

class Migrate extends Controller
{
    /**
     * Muestra información sobre el estado de las migraciones
     */
    public function index()
    {
        // En producción, bloquear el acceso público a las migraciones
        if (ENVIRONMENT === 'production' && !$this->request->getServer('REMOTE_ADDR') === '127.0.0.1') {
            return $this->response
                ->setStatusCode(403)
                ->setBody('Acceso denegado.');
        }

        $migrate = Services::migrations();

        try {
            $migrate->latest();
            return $this->response->setBody('Migraciones ejecutadas correctamente.');
        } catch (\Exception $e) {
            return $this->response
                ->setStatusCode(500)
                ->setBody('Error ejecutando migraciones: ' . $e->getMessage());
        }
    }

    /**
     * Ejecuta una migración específica (por versión)
     */
    public function version($version)
    {
        // En producción, bloquear el acceso público a las migraciones
        if (ENVIRONMENT === 'production' && !$this->request->getServer('REMOTE_ADDR') === '127.0.0.1') {
            return $this->response
                ->setStatusCode(403)
                ->setBody('Acceso denegado.');
        }

        $migrate = Services::migrations();

        try {
            $migrate->version((int)$version);
            return $this->response->setBody('Migración a versión ' . $version . ' completada.');
        } catch (\Exception $e) {
            return $this->response
                ->setStatusCode(500)
                ->setBody('Error ejecutando migración a versión: ' . $e->getMessage());
        }
    }

    /**
     * Revierte todas las migraciones
     */
    public function reset()
    {
        // En producción, bloquear el acceso público a las migraciones
        if (ENVIRONMENT === 'production' && !$this->request->getServer('REMOTE_ADDR') === '127.0.0.1') {
            return $this->response
                ->setStatusCode(403)
                ->setBody('Acceso denegado.');
        }

        $migrate = Services::migrations();

        try {
            $migrate->regress(0);
            return $this->response->setBody('Todas las migraciones han sido revertidas.');
        } catch (\Exception $e) {
            return $this->response
                ->setStatusCode(500)
                ->setBody('Error revirtiendo migraciones: ' . $e->getMessage());
        }
    }

    /**
     * Muestra el estado de todas las migraciones
     */
    public function status()
    {
        // En producción, bloquear el acceso público a las migraciones
        if (ENVIRONMENT === 'production' && !$this->request->getServer('REMOTE_ADDR') === '127.0.0.1') {
            return $this->response
                ->setStatusCode(403)
                ->setBody('Acceso denegado.');
        }

        $migrate = Services::migrations();

        try {
            $history = $migrate->getHistory();
            $files = $migrate->findMigrations();

            // Preparar vista con datos de migraciones
            $data = [
                'history' => $history,
                'files' => $files,
            ];

            // Si es una petición AJAX o CLI, devolver JSON
            if ($this->request->isAJAX() || $this->request->isCLI()) {
                return $this->response
                    ->setContentType('application/json')
                    ->setBody(json_encode($data));
            }

            // Construir salida HTML simple
            $output = '<h1>Estado de Migraciones</h1>';
            $output .= '<h2>Historial de Migraciones</h2>';

            if (empty($history)) {
                $output .= '<p>No hay migraciones ejecutadas.</p>';
            } else {
                $output .= '<ul>';
                foreach ($history as $migration) {
                    $output .= '<li>' . $migration->version . ' - ' . $migration->name . ' (' . $migration->time . ')</li>';
                }
                $output .= '</ul>';
            }

            $output .= '<h2>Archivos de Migración Disponibles</h2>';

            if (empty($files)) {
                $output .= '<p>No hay archivos de migración disponibles.</p>';
            } else {
                $output .= '<ul>';
                foreach ($files as $version => $file) {
                    $executed = array_key_exists($version, $history);
                    $output .= '<li>' . $version . ' - ' . basename($file) . ' ' . ($executed ? '<span style="color:green">(Ejecutada)</span>' : '<span style="color:red">(Pendiente)</span>') . '</li>';
                }
                $output .= '</ul>';
            }

            $output .= '<h2>Acciones</h2>';
            $output .= '<ul>';
            $output .= '<li><a href="' . site_url('migrate') . '">Ejecutar Migraciones Pendientes</a></li>';
            $output .= '<li><a href="' . site_url('migrate/reset') . '">Revertir Todas las Migraciones</a></li>';
            $output .= '</ul>';

            return $this->response->setBody($output);
        } catch (\Exception $e) {
            return $this->response
                ->setStatusCode(500)
                ->setBody('Error obteniendo estado de migraciones: ' . $e->getMessage());
        }
    }
}
