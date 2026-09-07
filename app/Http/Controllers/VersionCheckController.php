<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VersionCheckController extends Controller
{
    /**
     * Endpoint interrogé par un dashboard central pour relever la version
     * de l'application, de Laravel et de PHP.
     */
    public function __invoke(Request $request)
    {
        $expectedToken = config('app.version_check_token');

        if (! $expectedToken) {
            abort(404);
        }

        $providedToken = $request->header('X-Version-Token', '');

        if (! hash_equals($expectedToken, $providedToken)) {
            abort(403);
        }

        return response()->json([
            'app_name'        => config('app.name'),
            'app_version'     => config('app.version'),
            'laravel_version' => app()->version(),
            'php_version'     => PHP_VERSION,
            'environment'     => app()->environment(),
            'checked_at'      => now()->toIso8601String(),
        ]);
    }
}
