<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Abbreviation extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'document_analysis_id',
        'detected_element_id',
        'abbreviation',
        'full_form',
        'definition_element_index',
        'usage_count',
        'occurrences',
        'is_consistent',
        'inconsistent_forms',
    ];

    protected $casts = [
        'occurrences' => 'array',
        'inconsistent_forms' => 'array',
        'is_consistent' => 'boolean',
        'usage_count' => 'integer',
        'definition_element_index' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function documentAnalysis(): BelongsTo
    {
        return $this->belongsTo(DocumentAnalysis::class);
    }

    public function detectedElement(): BelongsTo
    {
        return $this->belongsTo(DetectedElement::class);
    }
}
