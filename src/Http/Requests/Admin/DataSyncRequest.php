<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

class DataSyncRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'dry_run' => ['nullable', 'boolean'],
        ];
    }
}
