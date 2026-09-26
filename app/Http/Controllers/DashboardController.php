<?php

namespace App\Http\Controllers;

use App\Models\Edl;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /** Au-delà, un EDL en cours est signalé comme en retard. */
    public const STALE_DAYS = 3;

    /** Colonnes légères : on évite survey_data et les signatures, volumineux. */
    private const COLUMNS = [
        'id', 'user_id', 'type', 'status', 'adresse', 'ville', 'entrant_id',
        'locataire_nom', 'locataire_prenom', 'technicien_prenom', 'technicien_nom',
        'date_edl', 'archived_at', 'created_at', 'updated_at',
    ];

    /**
     * Données de la page d'accueil : chiffres clés, EDL à reprendre et derniers EDL terminés.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        // Chiffres et derniers EDL terminés : tous les EDL. « À reprendre » : ceux de l'utilisateur
        // (un administrateur voit ceux de tout le monde).
        $scope = fn () => Edl::notArchived();
        $mine = fn () => $user->isAdmin() ? $scope() : $scope()->where('user_id', $user->id);
        $monthStart = now()->startOfMonth();

        return response()->json([
            'stats' => [
                'en_cours'       => $mine()->where('status', 'en_cours')->count(),
                'termines_mois'  => $scope()->where('status', 'complete')->where('updated_at', '>=', $monthStart)->count(),
                'entrants_mois'  => $scope()->where('type', 'entrant')->where('created_at', '>=', $monthStart)->count(),
                'sortants_mois'  => $scope()->where('type', 'sortant')->where('created_at', '>=', $monthStart)->count(),
                'total'          => $scope()->count(),
                // EDL « en cours » sans activité depuis plus de STALE_DAYS jours : à relancer
                'en_retard'      => $mine()->where('status', 'en_cours')->where('updated_at', '<', now()->subDays(self::STALE_DAYS))->count(),
            ],
            'en_cours' => $mine()->select(self::COLUMNS)->with('user')->where('status', 'en_cours')
                ->orderBy('updated_at')->orderBy('id')->limit(8)->get()
                ->map(fn ($edl) => $edl->setAttribute('en_retard', $edl->updated_at->lt(now()->subDays(self::STALE_DAYS)))),
            'recents' => $scope()->select(self::COLUMNS)->with('user')->where('status', 'complete')
                ->orderByDesc('updated_at')->orderByDesc('id')->limit(6)->get(),
        ]);
    }
}
