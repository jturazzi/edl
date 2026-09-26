<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetUserRole extends Command
{
    protected $signature = 'user:role {email : Adresse e-mail de l\'utilisateur} {role : admin ou technicien}';

    protected $description = 'Définit le rôle (admin ou technicien) d\'un utilisateur';

    public function handle(): int
    {
        $role = (string) $this->argument('role');

        if (! in_array($role, User::ROLES, true)) {
            $this->error('Rôle invalide : utilisez « ' . implode(' » ou « ', User::ROLES) . ' ».');

            return self::FAILURE;
        }

        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('Aucun utilisateur avec cette adresse e-mail.');

            return self::FAILURE;
        }

        $user->forceFill(['role' => $role])->save();
        $this->info("{$user->full_name} ({$user->email}) est maintenant « {$role} ».");

        return self::SUCCESS;
    }
}
