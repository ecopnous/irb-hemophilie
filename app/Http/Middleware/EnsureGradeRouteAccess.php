<?php

namespace App\Http\Middleware;

use App\Support\GradeNavigation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGradeRouteAccess
{
  public function handle(Request $request, Closure $next): Response
  {
    $routeName = $request->route()?->getName();

    if ($routeName && ! GradeNavigation::canAccessRoute($routeName)) {
      abort(403, 'Votre grade ne permet pas d\'accéder à cette page.');
    }

    return $next($request);
  }
}
