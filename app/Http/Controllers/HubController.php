<?php

namespace App\Http\Controllers;

// Halaman pusat untuk tab "Ibadah" dan "Belajar". @2026-Alifiandra
class HubController extends Controller
{
    public function worship()
    {
        return view('pages.hub.worship');
    }

    public function learning()
    {
        return view('pages.hub.learning');
    }
}
