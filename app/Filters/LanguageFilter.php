<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class LanguageFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Check if a language is set in the session
        if (session()->has('locale')) {
            // Set the language using the IncomingRequest method
            service('request')->setLocale(session()->get('locale'));
        } else {
            // Default to English if no language is set
            service('request')->setLocale('en');
            session()->set('locale', 'en');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing after the controller is executed
    }
}
