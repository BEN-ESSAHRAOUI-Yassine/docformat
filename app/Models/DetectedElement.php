<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetectedElement extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_analysis_id',
        'document_id',
        'type',
        'element_index',
        'content',
        'heading_level',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'heading_level' => 'integer',
        'element_index' => 'integer',
    ];

    public function documentAnalysis(): BelongsTo
    {
        return $this->belongsTo(DocumentAnalysis::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
