<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Support;

use ArrayAccess;
use JsonSerializable;
use LogicException;

final class LocationRecord implements ArrayAccess, JsonSerializable
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        private readonly array $attributes,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function code(): string
    {
        $code = $this->get('code');

        return is_string($code) ? $code : '';
    }

    public function nameFa(): ?string
    {
        $name = $this->get('name_fa');

        return is_string($name) && $name !== '' ? $name : null;
    }

    public function displayNameFa(): ?string
    {
        $displayName = $this->get('display_name_fa');

        if (is_string($displayName) && $displayName !== '') {
            return $displayName;
        }

        return $this->nameFa();
    }

    public function nameEn(): ?string
    {
        $name = $this->get('name_en');

        return is_string($name) && $name !== '' ? $name : null;
    }

    public function slug(): ?string
    {
        $slug = $this->get('slug');

        return is_string($slug) && $slug !== '' ? $slug : null;
    }

    public function label(): string
    {
        return $this->displayNameFa() ?? $this->nameEn() ?? $this->code();
    }

    /**
     * @return array{value: string, label: string, code: string, name_fa: mixed}
     */
    public function option(): array
    {
        return [
            'value' => $this->code(),
            'label' => $this->label(),
            'code' => $this->code(),
            'name_fa' => $this->get('name_fa'),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function with(array $attributes): self
    {
        return new self(array_merge($this->attributes, $attributes));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function offsetExists(mixed $offset): bool
    {
        if (! is_string($offset) && ! is_int($offset)) {
            return false;
        }

        return array_key_exists($offset, $this->attributes);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (! is_string($offset) && ! is_int($offset)) {
            return null;
        }

        return $this->attributes[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('LocationRecord is read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('LocationRecord is read-only.');
    }
}
