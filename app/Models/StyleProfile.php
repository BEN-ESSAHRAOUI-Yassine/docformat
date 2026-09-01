<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StyleProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'language',
        'version',
        'rules',
        'is_system',
        'user_id',
    ];

    protected $casts = [
        'rules' => 'array',
        'is_system' => 'boolean',
        'version' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function violations(): HasMany
    {
        return $this->hasMany(StyleViolation::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId)->orWhere('is_system', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }
}
