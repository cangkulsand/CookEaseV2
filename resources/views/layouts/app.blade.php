<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>CookEase</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://fonts.bunny.net/css?family=fraunces:400,500,600,700&display=swap" rel="stylesheet" />


        <!-- Alpine.js -->
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
        <script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script>
        
        <style>
            @media print {
                body * {
                    visibility: hidden;
                }
                #printableGroceryList, #printableGroceryList * {
                    visibility: visible;
                }
                #printableGroceryList {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                }
            }
        </style>
    </head>
    <script src="//unpkg.com/alpinejs" defer></script>

    <body class="font-sans antialiased">
        <div class="min-h-screen bg-white-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-12">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
    <footer class="bg-white rounded-lg shadow-sm">
        <div class="w-full max-w-screen-xl mx-auto p-4 md:py-8">
            <hr class="my-6 border-primary-600 sm:mx-auto lg:my-8" />
            <span class="block text-sm text-gray-500 text-center dark:text-gray-400">
                © 2025 <a href="{{ route('dashboard') }}" class="hover:underline">CookEase</a>. All Rights Reserved.
            </span>
        </div>
    </footer>
</html>
