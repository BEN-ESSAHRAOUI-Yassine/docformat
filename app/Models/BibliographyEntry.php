<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BibliographyEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'document_analysis_id',
        'detected_element_id',
        'entry_type',
        'authors',
        'title',
        'year',
        'journal',
        'publisher',
        'volume',
        'issue',
        'pages',
        'doi',
        'url',
        'access_date',
        'extra_fields',
        'raw_text',
        'element_index',
        'is_duplicate',
        'duplicate_group_id',
        'duplicate_confidence',
    ];

    protected $casts = [
        'authors' => 'array',
        'extra_fields' => 'array',
        'is_duplicate' => 'boolean',
        'duplicate_confidence' => 'float',
        'element_index' => 'integer',
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

    public function citations(): HasMany
    {
        return $this->hasMany(Citation::class);
    }
}
