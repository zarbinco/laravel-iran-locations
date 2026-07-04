<select name="{{ $name }}" {{ $attributes->merge(['class' => '']) }} @disabled($disabled) @required($required)>
    @if ($placeholder !== null)
        <option value="">{{ $placeholder }}</option>
    @endif

    @foreach ($options() as $option)
        @php
            $value = (string) $option->getKey();
            $current = $selectedValue();
        @endphp
        <option value="{{ $value }}" @selected((string) $current === $value) @if ($code($option) !== null) data-code="{{ $code($option) }}" @endif>
            {{ $label($option) }}
        </option>
    @endforeach
</select>
