@props(['disabled' => false])

<input
    @disabled($disabled)
    {{ $attributes->merge([
        'class' =>
            'w-full border-0 border-b-2 border-gray-300 bg-transparent text-sm text-gray-800 placeholder-gray-400 focus:border-primary-400 focus:ring-0 focus:outline-none pb-2 transition duration-150 ease-in-out'
    ]) }}
>
