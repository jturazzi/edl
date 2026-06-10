<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edl extends Model
{
    protected $fillable = [
        'type',
        'adresse',
        'ville',
        'survey_data',
        'signature',
        'pdf_path',
        'locataire_nom',
        'locataire_prenom',
        'locataire_email',
        'status',
        'date_edl',
        'user_id',
        'category_id',
    ];

    protected $casts = [
        'survey_data' => 'array',
        'date_edl'    => 'datetime',
    ];

    protected $appends = ['type_label', 'locataire_full_name', 'adresse_complete', 'agent_name'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Utilisateur ayant réalisé l'EDL.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(EdlPhoto::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'entrant'  => 'État des lieux entrant',
            'sortant'  => 'État des lieux sortant',
            default    => ucfirst($this->type),
        };
    }

    public function getLocataireFullNameAttribute(): string
    {
        return trim("{$this->locataire_prenom} {$this->locataire_nom}");
    }

    /** Adresse complète (adresse + ville) */
    public function getAdresseCompleteAttribute(): string
    {
        return trim("{$this->adresse}, {$this->ville}");
    }

    /** Nom de l'agent ayant réalisé l'EDL */
    public function getAgentNameAttribute(): string
    {
        return $this->user ? $this->user->full_name : 'Non renseigné';
    }
}
