<?php

/**
 * Helper para procesamiento de imágenes externas
 * Para ser cargado desde el controlador LlmProxy
 */

/**
 * Descarga una imagen externa de forma segura
 * 
 * @param string $imageUrl URL de la imagen a descargar
 * @param int $maxSize Tamaño máximo permitido en bytes (por defecto 4MB)
 * @param int $timeout Tiempo máximo de espera en segundos
 * @return array|null Array con datos en base64 y tipo MIME, o null si falla
 */
function download_external_image($imageUrl, $maxSize = 4194304, $timeout = 10)
{
    // Validar URL
    if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        log_error('IMAGE_PROXY', 'URL inválida: ' . $imageUrl);
        return null;
    }

    // Tipos MIME permitidos para imágenes
    $allowedMimeTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/bmp'
    ];

    try {
        // Inicializar cURL
        $ch = curl_init();

        // Configurar opciones de cURL
        curl_setopt_array($ch, [
            CURLOPT_URL => $imageUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false, // En producción considera habilitar esto
            CURLOPT_SSL_VERIFYHOST => 0,     // En producción considera cambiar a 2
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9,es;q=0.8',
                'Cache-Control: no-cache',
                'Pragma: no-cache'
            ],
            // Limitar el tamaño máximo de descarga
            CURLOPT_BUFFERSIZE => 128,
            CURLOPT_NOPROGRESS => false,
            CURLOPT_PROGRESSFUNCTION => function ($downloadSize, $downloaded) use ($maxSize) {
                // Abortar si el tamaño excede el máximo permitido
                return ($downloadSize > $maxSize || $downloaded > $maxSize) ? 1 : 0;
            }
        ]);

        // Ejecutar cURL
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        $downloadSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);

        // Cerrar cURL
        curl_close($ch);

        // Verificar errores y respuesta HTTP
        if ($error || $httpCode !== 200) {
            log_error('IMAGE_PROXY', 'Error descargando imagen: ' . $error . ', HTTP code: ' . $httpCode);
            return null;
        }

        // Verificar si se recibieron datos
        if (empty($imageData)) {
            log_error('IMAGE_PROXY', 'No se recibieron datos de la imagen');
            return null;
        }

        // Verificar tamaño
        if ($downloadSize > $maxSize) {
            log_error('IMAGE_PROXY', 'Imagen demasiado grande: ' . $downloadSize . ' bytes (máximo: ' . $maxSize . ' bytes)');
            return null;
        }

        // Verificar tipo MIME
        if (!in_array($contentType, $allowedMimeTypes)) {
            // Intentar detectar tipo MIME si el servidor no lo proporciona correctamente
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $detectedType = $finfo->buffer($imageData);

            if (!in_array($detectedType, $allowedMimeTypes)) {
                log_error('IMAGE_PROXY', 'Tipo MIME no permitido: ' . $contentType . ' (detectado: ' . $detectedType . ')');
                return null;
            }

            // Usar el tipo MIME detectado
            $contentType = $detectedType;
        }

        // Codificar en base64
        $base64Data = base64_encode($imageData);

        log_info('IMAGE_PROXY', 'Imagen descargada correctamente', [
            'url' => $imageUrl,
            'size' => $downloadSize,
            'mime_type' => $contentType
        ]);

        return [
            'data' => $base64Data,
            'mime_type' => $contentType,
            'size' => $downloadSize
        ];
    } catch (\Exception $e) {
        log_error('IMAGE_PROXY', 'Excepción al descargar imagen: ' . $e->getMessage());
        return null;
    }
}

/**
 * Procesa un payload con URLs de imágenes externas para convertirlas a base64
 * para los diferentes proveedores de IA
 * 
 * @param array $request_data Datos de la solicitud
 * @return array Payload modificado con imágenes en base64
 */
function process_external_images($request_data)
{
    // Verificar si hay imagen y es una URL externa
    if (
        !isset($request_data['hasImage']) || $request_data['hasImage'] !== true ||
        !isset($request_data['isExternalImageUrl']) || $request_data['isExternalImageUrl'] !== true
    ) {
        return $request_data;
    }

    log_info('IMAGE_PROXY', 'Procesando imágenes externas', [
        'provider' => $request_data['provider'] ?? 'unknown'
    ]);

    $provider = $request_data['provider'] ?? '';
    $model = $request_data['model'] ?? '';

    // Procesar según el proveedor
    switch ($provider) {
        case 'openai':
            return process_openai_images($request_data);

        case 'anthropic':
            return process_anthropic_images($request_data);

        case 'google':
            return process_google_images($request_data);

        case 'mistral':
            return process_mistral_images($request_data);

        default:
            log_warning('IMAGE_PROXY', 'Proveedor no soportado para procesamiento de imágenes: ' . $provider);
            return $request_data;
    }
}

/**
 * Procesa las imágenes para OpenAI (GPT-4 Vision)
 */
function process_openai_images($request_data)
{
    if (!isset($request_data['messages']) || !is_array($request_data['messages'])) {
        return $request_data;
    }

    $image_processed = false;

    foreach ($request_data['messages'] as &$message) {
        if ($message['role'] === 'user' && isset($message['content']) && is_array($message['content'])) {
            foreach ($message['content'] as &$content) {
                if (
                    isset($content['type']) && $content['type'] === 'image_url' &&
                    isset($content['image_url']['url'])
                ) {

                    $image_url = $content['image_url']['url'];

                    // Verificar si ya es una imagen base64
                    if (strpos($image_url, 'data:image/') === 0) {
                        continue;
                    }

                    log_info('IMAGE_PROXY', 'Descargando imagen para OpenAI', [
                        'url' => $image_url
                    ]);

                    // Descargar la imagen
                    $image_data = download_external_image($image_url);

                    if ($image_data) {
                        // Reemplazar la URL con datos base64
                        $content['image_url']['url'] = "data:{$image_data['mime_type']};base64,{$image_data['data']}";
                        $image_processed = true;

                        log_info('IMAGE_PROXY', 'Imagen procesada correctamente para OpenAI', [
                            'size' => $image_data['size'],
                            'mime_type' => $image_data['mime_type']
                        ]);
                    }
                }
            }
        }
    }

    // Eliminar el flag si se procesó alguna imagen
    if ($image_processed) {
        unset($request_data['isExternalImageUrl']);
    }

    return $request_data;
}

/**
 * Procesa las imágenes para Anthropic Claude
 */
function process_anthropic_images($request_data)
{
    if (!isset($request_data['messages']) || !is_array($request_data['messages'])) {
        return $request_data;
    }

    $image_processed = false;

    foreach ($request_data['messages'] as &$message) {
        if ($message['role'] === 'user' && isset($message['content']) && is_array($message['content'])) {
            foreach ($message['content'] as &$content) {
                if (
                    isset($content['type']) && $content['type'] === 'image' &&
                    isset($content['source']['type']) && $content['source']['type'] === 'url'
                ) {

                    $image_url = $content['source']['url'];

                    log_info('IMAGE_PROXY', 'Descargando imagen para Anthropic', [
                        'url' => $image_url
                    ]);

                    // Descargar la imagen
                    $image_data = download_external_image($image_url);

                    if ($image_data) {
                        // Reemplazar la URL con datos base64
                        $content['source'] = [
                            'type' => 'base64',
                            'media_type' => $image_data['mime_type'],
                            'data' => $image_data['data']
                        ];
                        $image_processed = true;

                        log_info('IMAGE_PROXY', 'Imagen procesada correctamente para Anthropic', [
                            'size' => $image_data['size'],
                            'mime_type' => $image_data['mime_type']
                        ]);
                    }
                }
            }
        }
    }

    // Eliminar el flag si se procesó alguna imagen
    if ($image_processed) {
        unset($request_data['isExternalImageUrl']);
    }

    return $request_data;
}

/**
 * Procesa las imágenes para Google Gemini
 */
function process_google_images($request_data)
{
    if (!isset($request_data['contents']) || !is_array($request_data['contents'])) {
        // Adaptar para el formato de la API de Gemini
        if (isset($request_data['messages']) && is_array($request_data['messages'])) {
            foreach ($request_data['messages'] as &$message) {
                if ($message['role'] === 'user' && isset($message['parts']) && is_array($message['parts'])) {
                    foreach ($message['parts'] as &$part) {
                        if (isset($part['inlineData']) && isset($part['inlineData']['imageUrl'])) {
                            $image_url = $part['inlineData']['imageUrl'];

                            log_info('IMAGE_PROXY', 'Descargando imagen para Google', [
                                'url' => $image_url
                            ]);

                            $image_data = download_external_image($image_url);

                            if ($image_data) {
                                unset($part['inlineData']['imageUrl']);
                                $part['inlineData'] = [
                                    'mimeType' => $image_data['mime_type'],
                                    'data' => $image_data['data']
                                ];

                                log_info('IMAGE_PROXY', 'Imagen procesada correctamente para Google', [
                                    'size' => $image_data['size'],
                                    'mime_type' => $image_data['mime_type']
                                ]);

                                unset($request_data['isExternalImageUrl']);
                            }
                        }
                    }
                }
            }
        }

        return $request_data;
    }

    $image_processed = false;

    foreach ($request_data['contents'] as &$content) {
        if (isset($content['parts']) && is_array($content['parts'])) {
            foreach ($content['parts'] as &$part) {
                if (isset($part['inline_data']) && isset($part['inline_data']['url'])) {
                    $image_url = $part['inline_data']['url'];

                    log_info('IMAGE_PROXY', 'Descargando imagen para Google', [
                        'url' => $image_url
                    ]);

                    // Descargar la imagen
                    $image_data = download_external_image($image_url);

                    if ($image_data) {
                        // Reemplazar con datos inline
                        unset($part['inline_data']['url']);
                        $part['inline_data'] = [
                            'mime_type' => $image_data['mime_type'],
                            'data' => $image_data['data']
                        ];
                        $image_processed = true;

                        log_info('IMAGE_PROXY', 'Imagen procesada correctamente para Google', [
                            'size' => $image_data['size'],
                            'mime_type' => $image_data['mime_type']
                        ]);
                    }
                }
            }
        }
    }

    // Eliminar el flag si se procesó alguna imagen
    if ($image_processed) {
        unset($request_data['isExternalImageUrl']);
    }

    return $request_data;
}

/**
 * Procesa las imágenes para Mistral AI
 */
function process_mistral_images($request_data)
{
    if (!isset($request_data['messages']) || !is_array($request_data['messages'])) {
        return $request_data;
    }

    $image_processed = false;

    foreach ($request_data['messages'] as &$message) {
        if ($message['role'] === 'user' && isset($message['content']) && is_array($message['content'])) {
            foreach ($message['content'] as &$content) {
                // Formato específico para Mistral
                if (
                    isset($content['type']) && $content['type'] === 'image' &&
                    isset($content['source']['url'])
                ) {

                    $image_url = $content['source']['url'];

                    log_info('IMAGE_PROXY', 'Descargando imagen para Mistral', [
                        'url' => $image_url
                    ]);

                    // Descargar la imagen
                    $image_data = download_external_image($image_url);

                    if ($image_data) {
                        // Reemplazar la URL con datos base64
                        $content['source'] = [
                            'type' => 'base64',
                            'media_type' => $image_data['mime_type'],
                            'data' => $image_data['data']
                        ];
                        $image_processed = true;

                        log_info('IMAGE_PROXY', 'Imagen procesada correctamente para Mistral', [
                            'size' => $image_data['size'],
                            'mime_type' => $image_data['mime_type']
                        ]);
                    }
                }
            }
        }
    }

    // Eliminar el flag si se procesó alguna imagen
    if ($image_processed) {
        unset($request_data['isExternalImageUrl']);
    }

    return $request_data;
}
