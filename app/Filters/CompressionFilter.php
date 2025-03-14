<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CompressionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Only compress if the client accepts it
        $acceptEncoding = $request->getHeaderLine('Accept-Encoding');
        
        // Skip compression for streaming responses
        if (strpos($response->getHeaderLine('Content-Type'), 'text/event-stream') !== false) {
            return $response;
        }

        // Check response size - don't compress small responses
        $content = $response->getBody();
        if (strlen($content) < 1024) { // Skip if less than 1KB
            return $response;
        }

        // Use gzip compression if accepted
        if (strpos($acceptEncoding, 'gzip') !== false) {
            $compressed = gzencode($content, 6); // Medium compression level
            if ($compressed !== false) {
                return $response
                    ->setBody($compressed)
                    ->setHeader('Content-Encoding', 'gzip')
                    ->setHeader('Vary', 'Accept-Encoding, Origin, Authorization');
            }
        }

        return $response;
    }
}
