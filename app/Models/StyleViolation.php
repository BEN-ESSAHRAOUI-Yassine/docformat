<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StyleViolation extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_analysis_id',
        'detected_element_id',
        'check_type',
        'expected_value',
        'actual_value',
        'severity',
        'category',
        'description',
        'recommendation',
    ];

    protected $casts = [
        'expected_value' => 'array',
        'actual_value' => 'array',
    ];

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(DocumentAnalysis::class, 'document_analysis_id');
    }

    public function element(): BelongsTo
    {
        return $this->belongsTo(DetectedElement::class, 'detected_element_id');
    }

    public function scopeForSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
