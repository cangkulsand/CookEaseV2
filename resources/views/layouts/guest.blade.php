@props([
    'backgroundImage' => asset('images/background-food.png'),
    'sideImage' => null,
    'imagePosition' => 'left',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Register</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://fonts.bunny.net/css?family=fraunces:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-cover bg-top"
         style="background-image: url('{{ $backgroundImage }}')">

        <div class="w-full max-w-md sm:max-w-lg md:max-w-2xl bg-white shadow-lg rounded-2xl flex flex-col 
            md:flex-row {{ $imagePosition === 'right' ? 'md:flex-row-reverse' : '' }} overflow-hidden">

            @if ($sideImage)    
                <div class="hidden md:block md:w-2/5">
                    <img src="{{ $sideImage }}" alt="Visual" class="h-full w-full object-cover">
                </div>
            @endif

            <div class="w-full md:w-3/5 p-6 sm:p-10">
                {{ $slot }}
            </div>

        </div>
    </div>
</body>
</html>
