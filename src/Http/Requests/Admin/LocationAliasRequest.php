<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

use Illuminate\Validation\Validator;
use Zarbin\IranLocations\Support\LocationModelResolver;

class LocationAliasRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'location_type' => ['required', 'in:province,city,city_region,city_area,neighborhood'],
            'location_id' => ['required', 'integer'],
            'alias' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
            'source' => $this->sourceRule(),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $type = $this->input('location_type');

                if (! is_string($type)) {
                    return;
                }

                if (! in_array($type, ['province', 'city', 'city_region', 'city_area', 'neighborhood'], true)) {
                    return;
                }

                $class = LocationModelResolver::model($type);

                if (! $class::query()->whereKey($this->input('location_id'))->exists()) {
                    $validator->errors()->add('location_id', 'The selected location does not exist.');
                }
            },
        ];
    }
}
