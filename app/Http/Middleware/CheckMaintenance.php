<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Helpers\ApiResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckMaintenance
{
    public function handle(Request $request, Closure $next)
    {
        $maintenance = Setting::value('under_maintenance');

        if ($maintenance) {
            try {
                JWTAuth::invalidate(JWTAuth::getToken());
            } catch (\Exception $e) {}

            return ApiResponse::error('System under maintenance. please wait 30 minutes.', 200);
        }

        return $next($request);
    }
}
