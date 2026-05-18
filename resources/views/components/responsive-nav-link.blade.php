@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-primary-400 dark:border-primary-600 text-start text-base font-medium text-primary-700 dark:text-primary-300 bg-primary-50 dark:bg-primary-900/50 focus:outline-none focus:text-primary-800 dark:focus:text-primary-200 focus:bg-primary-100 dark:focus:bg-primary-900 focus:border-primary-700 dark:focus:border-primary-300 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 dark:text-gray-400 hover:text-primary-800 dark:hover:text-primary-200 hover:bg-primary-50 dark:hover:bg-primary-700 hover:border-primary-300 dark:hover:border-primary-600 focus:outline-none focus:text-primary-800 dark:focus:text-primary-200 focus:bg-primary-50 dark:focus:bg-primary-700 focus:border-primary-300 dark:focus:border-primary-600 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
