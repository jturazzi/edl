<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

    /**
     * Liste des utilisateurs avec leur rôle et leur nombre d'EDL.
     */
    public function users()
    {
        $users = User::withCount('edls')->orderBy('name')->get()->map(fn (User $u) => [
            'id'         => $u->id,
            'name'       => $u->full_name,
            'email'      => $u->email,
            'role'       => $u->role,
            'edls_count' => $u->edls_count,
        ]);

        return response()->json($users);
    }

    /**
     * Change le rôle d'un utilisateur (au moins un administrateur doit toujours rester).
     */
    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate(['role' => ['required', Rule::in(User::ROLES)]]);

        if ($user->isAdmin() && $data['role'] !== User::ROLE_ADMIN && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            return response()->json(['message' => 'Il doit rester au moins un administrateur.'], 422);
        }

        if ($user->role !== $data['role']) {
            $previous = $user->role;
            $user->forceFill(['role' => $data['role']])->save();

            ActivityLogger::userRoleChanged($user->id, [
                'name' => $user->full_name,
                'from' => $previous,
                'to'   => $user->role,
            ]);
        }

        return response()->json(['id' => $user->id, 'role' => $user->role]);
    }
}
