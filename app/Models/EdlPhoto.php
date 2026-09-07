<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $edl_id
 * @property string $question_key
 * @property string $room
 * @property string $photo_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Edl $edl
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EdlPhoto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EdlPhoto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EdlPhoto query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EdlPhoto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EdlPhoto whereEdlId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EdlPhoto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EdlPhoto wherePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EdlPhoto whereQuestionKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EdlPhoto whereRoom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EdlPhoto whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
