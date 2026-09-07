<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $category_id
 * @property string $type
 * @property string|null $adresse
 * @property string|null $ville
 * @property array<array-key, mixed>|null $survey_data
 * @property string|null $signature
 * @property string|null $pdf_path
 * @property string|null $locataire_nom
 * @property string|null $locataire_prenom
 * @property string|null $locataire_email
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $date_edl
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Category|null $category
 * @property-read string $adresse_complete
 * @property-read string $agent_name
 * @property-read string $locataire_full_name
 * @property-read string $type_label
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EdlPhoto> $photos
 * @property-read int|null $photos_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl whereAdresse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl whereDateEdl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl whereLocataireEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl whereLocataireNom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl whereLocatairePrenom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl wherePdfPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl whereSignature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl whereSurveyData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl whereVille($value)
 * @mixin \Eloquent
 */
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
