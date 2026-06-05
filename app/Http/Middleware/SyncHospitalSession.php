<?php

namespace App\Http\Middleware;

use App\Services\HospitalSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncHospitalSession
{
    public function __construct(
        protected HospitalSessionService $hospitalSessionService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->hospitalSessionService->sync($request, $request->user());

        return $next($request);
    }
}
