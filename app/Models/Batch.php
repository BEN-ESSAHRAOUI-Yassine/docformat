<?php

namespace App\Models;

use App\Enums\BatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'style_profile_id',
        'name',
        'status',
        'summary',
    ];

    protected $casts = [
        'status' => BatchStatus::class,
        'summary' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function styleProfile(): BelongsTo
    {
        return $this->belongsTo(StyleProfile::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BatchItem::class);
    }

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }
}
