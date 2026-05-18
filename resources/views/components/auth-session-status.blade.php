@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-primary-600 dark:text-primary-400']) }}>
        {{ $status }}
    </div>
@endif
