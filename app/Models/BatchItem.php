<?php

namespace App\Models;

use App\Enums\BatchItemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'document_id',
        'status',
        'quality_score',
        'error',
    ];

    protected $casts = [
        'status' => BatchItemStatus::class,
        'quality_score' => 'float',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
