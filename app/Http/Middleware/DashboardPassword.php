<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $password = config('app.dashboard_password');

        // Jika password belum diset, langsung allow
        if (!$password) {
            return $next($request);
        }

        // Sudah terautentikasi
        if ($request->session()->get('dashboard_auth') === true) {
            return $next($request);
        }

        // Redirect ke login
        return redirect()->route('dashboard.login');
    }
}
