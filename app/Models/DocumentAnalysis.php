<?php

namespace App\Models;

use App\Enums\AnalysisStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'document_version_id',
        'status',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'status' => AnalysisStatus::class,
        'metadata' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class);
    }

    public function detectedElements(): HasMany
    {
        return $this->hasMany(DetectedElement::class);
    }
}
