<?php

namespace App\Http\Controllers;

use App\Models\Edl;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Statistiques EDL pour la page admin.
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

        return response()->json([
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
