<?php

namespace App\Policies;

use App\Models\Edl;
use App\Models\User;

/**
 * Tous les utilisateurs connectés consultent tous les EDL. Chacun ne modifie que les siens ;
 * seuls les administrateurs modifient ceux des autres, archivent et suppriment.
 */
class EdlPolicy
{
    public function view(User $user, Edl $edl): bool
    {
        return true;
    }

    public function update(User $user, Edl $edl): bool
    {
        return $user->isAdmin() || ($edl->user_id !== null && $edl->user_id === $user->id);
    }

    /** Suppression : réservée aux administrateurs. */
    public function delete(User $user, Edl $edl): bool
    {
        return $user->isAdmin();
    }

    /** Archivage / désarchivage : réservé aux administrateurs. */
    public function archive(User $user, Edl $edl): bool
    {
        return $user->isAdmin();
    }
}
