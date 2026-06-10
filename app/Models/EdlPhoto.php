<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdlPhoto extends Model
{
    protected $fillable = [
        'edl_id',
        'question_key',
        'room',
        'photo_path',
    ];

    public function edl(): BelongsTo
    {
        return $this->belongsTo(Edl::class);
    }
}
