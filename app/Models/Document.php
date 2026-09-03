<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\EnforcementMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'original_filename',
        'project_id',
        'status',
        'language',
        'enforcement_mode',
        'current_version_id',
        'file_hash',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'enforcement_mode' => EnforcementMode::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class)->latestOfMany('version_number');
    }

    public function elements(): HasMany
    {
        return $this->hasMany(DocumentElement::class);
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(DocumentAnalysis::class);
    }

    public function latestAnalysis(): HasOne
    {
        return $this->hasOne(DocumentAnalysis::class)->latestOfMany();
    }

    public function citations(): HasMany
    {
        return $this->hasMany(Citation::class);
    }

    public function bibliographyEntries(): HasMany
    {
        return $this->hasMany(BibliographyEntry::class);
    }

    public function abbreviations(): HasMany
    {
        return $this->hasMany(Abbreviation::class);
    }
}
