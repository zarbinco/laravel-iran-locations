<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Zarbin\IranLocations\Support\LocationModelResolver;

trait HasLocationReplacement
{
    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo($this->replacementModelClass(), 'replaced_by_id');
    }

    public function replacements(): HasMany
    {
        return $this->hasMany($this->replacementModelClass(), 'replaced_by_id');
    }

    private function replacementModelClass(): string
    {
        return LocationModelResolver::model($this->modelConfigKey);
    }
}
