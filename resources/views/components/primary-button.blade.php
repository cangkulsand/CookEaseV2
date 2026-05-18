<button
    {{ $attributes->merge([
        'type' => 'submit',
        'class' =>
            'w-full bg-primary-400 hover:bg-primary-500 text-black font-semibold text-sm rounded-md py-3 px-6 transition duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-400 focus:ring-offset-2'
    ]) }}
    >
    {{ $slot }}
</button>
