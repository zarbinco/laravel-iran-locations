<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasLocationStatus
{
    public function isActive(): bool
    {
        return (bool) $this->getAttribute('is_active') && ! $this->isDeprecated();
    }

    public function isInactive(): bool
    {
        return ! (bool) $this->getAttribute('is_active');
    }

    public function isDeprecated(): bool
    {
        return $this->getAttribute('deprecated_at') !== null;
    }

    public function markActive(): static
    {
        $this->setAttribute('is_active', true);

        return $this;
    }

    public function markInactive(): static
    {
        $this->setAttribute('is_active', false);

        return $this;
    }

    public function markDeprecated(?Model $replacement = null): static
    {
        $this->setAttribute('is_active', false);
        $this->setAttribute('deprecated_at', $this->freshTimestamp());

        if ($replacement !== null && $replacement->getKey() !== null) {
            $this->setAttribute('replaced_by_id', $replacement->getKey());
        }

        return $this;
    }

    public function restoreFromDeprecation(): static
    {
        $this->setAttribute('is_active', true);
        $this->setAttribute('deprecated_at', null);
        $this->setAttribute('replaced_by_id', null);

        return $this;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNull('deprecated_at');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeDeprecated(Builder $query): Builder
    {
        return $query->whereNotNull('deprecated_at');
    }
}
