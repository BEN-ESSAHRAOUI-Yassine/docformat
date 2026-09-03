<?php

namespace App\Models;

use App\Enums\ActionOrigin;
use App\Enums\Reversibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'user_id',
        'action_type',
        'element_type',
        'element_id',
        'origin',
        'old_value',
        'new_value',
        'payload',
        'reversibility',
        'bulk_id',
        'undone_at',
    ];

    protected $casts = [
        'origin' => ActionOrigin::class,
        'reversibility' => Reversibility::class,
        'old_value' => 'array',
        'new_value' => 'array',
        'payload' => 'array',
        'undone_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReversible(): bool
    {
        return $this->reversibility->isUndoable();
    }

    public function scopeForDocument($query, int $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    public function scopeBetweenDates($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }

    public function scopeInBulk($query, string $bulkId)
    {
        return $query->where('bulk_id', $bulkId);
    }

    public function scopeUndone($query)
    {
        return $query->whereNotNull('undone_at');
    }

    public function scopeNotUndone($query)
    {
        return $query->whereNull('undone_at');
    }

    public function scopeReversible($query)
    {
        return $query->where('reversibility', '!=', Reversibility::None->value);
    }
}
