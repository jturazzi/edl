<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $type
 * @property string|null $adresse
 * @property string|null $ville
 * @property string|null $technicien_prenom
 * @property string|null $technicien_nom
 * @property string|null $technicien_email
 * @property array<array-key, mixed>|null $survey_data
 * @property list<string>|null $steps
 * @property int $survey_rev
 * @property \Illuminate\Support\Carbon|null $signed_at
 * @property string|null $pdf_hash
 * @property list<array{label: string, amount: float}>|null $retenues
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property string|null $signature
 * @property string|null $signature_technicien
 * @property bool $locataire_absent
 * @property int|null $entrant_id
 * @property string|null $pdf_path
 * @property string|null $locataire_nom
 * @property string|null $locataire_prenom
 * @property string|null $locataire_email
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $date_edl
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $adresse_complete
 * @property-read string $agent_name
 * @property-read string $numero
 * @property-read string $locataire_full_name
 * @property-read string $type_label
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EdlPhoto> $photos
 * @property-read int|null $photos_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edl whereAdresse($value)
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
    use HasFactory;

    protected $fillable = [
        'type',
        'adresse',
        'ville',
        'technicien_prenom',
        'technicien_nom',
        'technicien_email',
        'survey_data',
        'survey_rev',
        'steps',
        'retenues',
        'archived_at',
        'signature',
        'signature_technicien',
        'locataire_absent',
        'entrant_id',
        'pdf_path',
        'pdf_hash',
        'signed_at',
        'locataire_nom',
        'locataire_prenom',
        'locataire_email',
        'status',
        'date_edl',
        'user_id',
    ];

    /** Les signatures (base64 volumineuses) ne sont jamais renvoyées dans les réponses JSON. */
    protected $hidden = ['signature', 'signature_technicien'];

    protected $casts = [
        'survey_data' => 'array',
        'steps'       => 'array',
        'retenues'    => 'array',
        'archived_at' => 'datetime',
        'signed_at'   => 'datetime',
        'survey_rev'  => 'integer',
        'date_edl'    => 'datetime',
        'locataire_absent' => 'boolean',
    ];

    protected $appends = ['type_label', 'locataire_full_name', 'adresse_complete', 'agent_name', 'numero'];

    /**
     * Utilisateur ayant réalisé l'EDL.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * EDL entrant auquel ce sortant se rapporte (comparaison des états).
     *
     * @return BelongsTo<Edl, $this>
     */
    public function entrant(): BelongsTo
    {
        return $this->belongsTo(self::class, 'entrant_id');
    }

    /** Un EDL terminé (signé) n'est plus modifiable. */
    public function isLocked(): bool
    {
        return $this->status === 'complete';
    }

    /**
     * Exclut les EDL archivés.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Edl>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Edl>
     */
    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
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
    /**
     * Numéro d'EDL affiché partout (PDF, e-mail, historique) : dérivé de l'id en base.
     */
    public function getNumeroAttribute(): string
    {
        return 'EDL-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getAgentNameAttribute(): string
    {
        $technicien = trim(($this->technicien_prenom ?? '') . ' ' . ($this->technicien_nom ?? ''));

        if ($technicien !== '') {
            return $technicien;
        }

        return $this->user ? $this->user->full_name : 'Non renseigné';
    }
}
