<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'microsoft_id',
        'firstname',
        'lastname',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'remember_token',
    ];

    protected $appends = ['full_name'];

    /**
     * Nom complet de l'utilisateur.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->firstname || $this->lastname) {
            return trim("{$this->firstname} {$this->lastname}");
        }
        return $this->name;
    }

    /**
     * EDL réalisés par cet utilisateur.
     */
    public function edls(): HasMany
    {
        return $this->hasMany(Edl::class);
    }
}
