<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublicSiteController extends Controller
{
    /**
     * Tampilkan halaman utama (Beranda).
     */
    public function home()
    {
        return view('public.home');
    }

    /**
     * Tampilkan halaman portofolio klien.
     */
    public function clients(Request $request)
    {
        return view('public.clients');
    }

    public function sitemap()
    {
        return response()
            ->view('public.sitemap')
            ->header('Content-Type', 'text/xml');
    }
}
