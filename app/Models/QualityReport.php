<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'document_analysis_id',
        'quality_score',
        'sections',
        'summary',
        'generated_at',
    ];

    protected $casts = [
        'quality_score' => 'array',
        'sections' => 'array',
        'summary' => 'array',
        'generated_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(DocumentAnalysis::class, 'document_analysis_id');
    }
}
