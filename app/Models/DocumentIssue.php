<?php

namespace App\Models;

use App\Enums\IssueCategory;
use App\Enums\IssueDecision;
use App\Enums\IssueSource;
use App\Enums\ReviewMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'document_analysis_id',
        'detected_element_id',
        'source',
        'category',
        'severity',
        'description',
        'recommendation',
        'location',
        'decision',
        'ignored_reason',
        'review_mode',
        'probabilistic',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'source' => IssueSource::class,
        'category' => IssueCategory::class,
        'decision' => IssueDecision::class,
        'review_mode' => ReviewMode::class,
        'location' => 'array',
        'probabilistic' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(DocumentAnalysis::class, 'document_analysis_id');
    }

    public function element(): BelongsTo
    {
        return $this->belongsTo(DetectedElement::class, 'detected_element_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeForDocument($query, int $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    public function scopeForSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByDecision($query, string $decision)
    {
        return $query->where('decision', $decision);
    }

    public function scopeInReviewMode($query, ?string $mode)
    {
        if (! $mode || $mode === 'all') {
            return $query;
        }

        return $query->where('review_mode', $mode);
    }

    public function scopePending($query)
    {
        return $query->where('decision', IssueDecision::Pending->value);
    }
}
