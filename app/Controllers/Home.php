<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        return view('landing', [
            'title' => 'AI Text Enhancer Pro - Potencia tu contenido con IA'
        ]);
    }
}
