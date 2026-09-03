<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Citation extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'document_analysis_id',
        'detected_element_id',
        'type',
        'raw_text',
        'author',
        'year',
        'numbers',
        'element_index',
        'confidence',
        'metadata',
        'bibliography_entry_id',
    ];

    protected $casts = [
        'numbers' => 'array',
        'metadata' => 'array',
        'confidence' => 'float',
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

    public function bibliographyEntry(): BelongsTo
    {
        return $this->belongsTo(BibliographyEntry::class);
    }
}
