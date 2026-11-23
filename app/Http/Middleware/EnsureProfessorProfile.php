<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class EnsureProfessorProfile {
 public function handle(Request $request, Closure $next): Response {
   if ($request->user() && !$request->user()->professor) { abort(403,'La cuenta no tiene un perfil docente asociado.'); }
   return $next($request);
 }
}
