<?php

namespace App\Http\Controllers;

use App\Models\Edl;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Informations système pour la page admin.
     */
    public function info()
    {
        $stats = DB::table('edls')->selectRaw("
            COUNT(*) as edl_total,
            SUM(CASE WHEN type = 'entrant' THEN 1 ELSE 0 END) as edl_entrant,
            SUM(CASE WHEN type = 'sortant' THEN 1 ELSE 0 END) as edl_sortant,
            SUM(CASE WHEN status = 'en_cours' THEN 1 ELSE 0 END) as edl_en_cours,
            SUM(CASE WHEN status = 'complete' THEN 1 ELSE 0 END) as edl_complete
        ")->first();

        $edlTotal    = (int) $stats->edl_total;
        $edlEntrant  = (int) $stats->edl_entrant;
        $edlSortant  = (int) $stats->edl_sortant;
        $edlEnCours  = (int) $stats->edl_en_cours;
        $edlComplete = (int) $stats->edl_complete;

        // Détection du serveur
        $server = 'Inconnu';
        if (isset($_SERVER['SERVER_SOFTWARE'])) {
            $sw = $_SERVER['SERVER_SOFTWARE'];
            if (stripos($sw, 'frankenphp') !== false) {
                $server = 'FrankenPHP';
            } elseif (stripos($sw, 'caddy') !== false) {
                $server = 'Caddy';
            } elseif (stripos($sw, 'nginx') !== false) {
                $server = 'Nginx';
            } elseif (stripos($sw, 'apache') !== false) {
                $server = 'Apache';
            } else {
                $server = $sw;
            }
        } elseif (php_sapi_name() === 'frankenphp') {
            $server = 'FrankenPHP';
        }

        return response()->json([
            'app' => [
                'version'     => config('app.version', 'v1.0.0'),
                'environment' => config('app.env'),
                'debug'       => config('app.debug'),
                'timezone'    => config('app.timezone'),
                'url'         => config('app.url'),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'sapi'    => php_sapi_name(),
            ],
            'laravel' => [
                'version' => app()->version(),
            ],
            'server' => $server,
            'database' => [
                'driver'   => config('database.default'),
                'database' => basename(config('database.connections.' . config('database.default') . '.database', '')),
            ],
            'cache'   => config('cache.default'),
            'session' => config('session.driver'),
            'stats' => [
                'edl_total'    => $edlTotal,
                'edl_entrant'  => $edlEntrant,
                'edl_sortant'  => $edlSortant,
                'edl_en_cours' => $edlEnCours,
                'edl_complete' => $edlComplete,
            ],
        ]);
    }
}
