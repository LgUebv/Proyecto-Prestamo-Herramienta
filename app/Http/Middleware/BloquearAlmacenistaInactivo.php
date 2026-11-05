<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BloquearAlmacenistaInactivo
{
    /**
     * Cierra la sesión de un almacenista que fue marcado como inactivo mientras estaba conectado.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user && $user->esAlmacenistaInactivo()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // En peticiones de Livewire, 419 hace que la página se recargue y termine en el login
            if ($request->hasHeader('X-Livewire')) {
                abort(419);
            }

            return redirect()->route('login')->withErrors([
                'email' => __('Tu cuenta de almacenista está inactiva. Contacta al administrador del almacén.'),
            ]);
        }

        return $next($request);
    }
}
