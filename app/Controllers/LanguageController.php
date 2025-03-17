<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class LanguageController extends Controller
{
    public function setLanguage($locale)
    {
        // Get App config
        $config = new \Config\App();
        
        if (in_array($locale, $config->supportedLocales)) {
            session()->set('locale', $locale);
        } else {
            session()->set('locale', 'en'); // Default to English if locale not supported
        }
        
        // Redirect back to the previous page
        return redirect()->back();
    }
}
