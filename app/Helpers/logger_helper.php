<?php

if (!function_exists('log_custom')) {
    /**
     * Registra un mensaje con nivel y sección personalizada
     *
     * @param string $level Nivel de log (debug, info, warning, error)
     * @param string $section Sección o categoría del log
     * @param string $message Mensaje a registrar
     * @param mixed $data Datos adicionales (opcional)
     * @return bool Éxito o fracaso
     */
    function log_custom($level, $section, $message, $data = null)
    {
        $request = service('request');

        $log_message = "[{$section}] {$message}";

        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                $log_message .= " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
            } else {
                $log_message .= " | Data: {$data}";
            }
        }

        // Añadir información de la petición
        $request_info = [
            'IP' => $request->getIPAddress(),
            'Method' => $request->getMethod(),
            'URL' => current_url(),
            'User Agent' => $request->getUserAgent()->getAgentString(),
        ];

        $log_message .= " | Request: " . json_encode($request_info, JSON_UNESCAPED_UNICODE);

        log_message($level, $log_message);
        return true;
    }
}

if (!function_exists('log_debug')) {
    /**
     * Registra un mensaje de depuración
     * 
     * @param string $section Sección o categoría del log
     * @param string $message Mensaje a registrar
     * @param mixed $data Datos adicionales (opcional)
     * @return bool Éxito o fracaso
     */
    function log_debug($section, $message, $data = null)
    {
        return log_custom('debug', $section, $message, $data);
    }
}

if (!function_exists('log_info')) {
    /**
     * Registra un mensaje informativo
     * 
     * @param string $section Sección o categoría del log
     * @param string $message Mensaje a registrar
     * @param mixed $data Datos adicionales (opcional)
     * @return bool Éxito o fracaso
     */
    function log_info($section, $message, $data = null)
    {
        return log_custom('info', $section, $message, $data);
    }
}

if (!function_exists('log_error')) {
    /**
     * Registra un mensaje de error
     * 
     * @param string $section Sección o categoría del log
     * @param string $message Mensaje a registrar
     * @param mixed $data Datos adicionales (opcional)
     * @return bool Éxito o fracaso
     */
    function log_error($section, $message, $data = null)
    {
        return log_custom('error', $section, $message, $data);
    }
}

if (!function_exists('log_warning')) {
    /**
     * Registra un mensaje de advertencia
     * 
     * @param string $section Sección o categoría del log
     * @param string $message Mensaje a registrar
     * @param mixed $data Datos adicionales (opcional)
     * @return bool Éxito o fracaso
     */
    function log_warning($section, $message, $data = null)
    {
        return log_custom('warning', $section, $message, $data);
    }
}
