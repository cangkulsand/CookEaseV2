<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Generate Recipe') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto p-6 lg:p-8">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-2 sm:p-4">
                <h1 class="text-black text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-semibold font-['Inter'] mb-4">
                    Malaysian Ingredients, Infinite Recipes.
                </h1>

                @if (session('message'))
                    <div class="mb-4 p-4 bg-primary-100 text-primary-700 rounded">
                        {{ session('message') }}
                    </div>
                @endif

                <form class="w-full mb-6" method="POST" action="{{ route('generate.process') }}" onsubmit="showLoading()">
                    @csrf
                    <div class="mb-4" x-data="{ showFilters: false }">
                        <label for="ingredients" class="block text-gray-700 font-medium mb-2">
                            Share your ingredients, set your taste, and let CookEase suggest the
                            perfect Malaysian recipe.
                        </label>

                        <div class="relative">
                            <input type="text" name="ingredients" id="ingredients"
                                placeholder="e.g., chicken, lemongrass, coconut milk"
                                class="w-full pl-4 pr-20 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-400"
                                required>

                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 space-x-2">
                                <button type="button" @click="showFilters = !showFilters"
                                    class="text-gray-500 hover:text-primary-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L15 12.414V19a1 1 0 01-1.447.894l-4-2A1 1 0 019 17v-4.586L3.293 6.707A1 1 0 013 6V4z" />
                                    </svg>
                                </button>

                                <button type="submit" class="text-gray-500 hover:text-primary-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-4.35-4.35M16.65 16.65A7.5 7.5 0 1116.65 2a7.5 7.5 0 010 14.65z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div x-show="showFilters" x-transition class="mt-4 p-4 bg-gray-50 border rounded-lg space-y-4">
                            <div>
                                <label class="font-semibold">Preferences</label>
                                <div class="flex flex-wrap gap-2 mt-1">
                                    <label><input type="checkbox" name="filters[]" value="halal"> Halal</label>
                                    <label><input type="checkbox" name="filters[]" value="vegetarian"> Vegetarian</label>
                                    <label><input type="checkbox" name="filters[]" value="lowcarb"> Low Carb</label>
                                    <label><input type="checkbox" name="filters[]" value="highProtein"> High Protein</label>
                                    <label><input type="checkbox" name="filters[]" value="glutenFree"> Gluten-Free</label>
                                    <label><input type="checkbox" name="filters[]" value="dairyFree"> Dairy-Free</label>
                                </div>
                            </div>

                            <div>
                                <label class="font-semibold">Cooking Time</label>
                                <div class="flex flex-wrap gap-2 mt-1">
                                    <label><input type="radio" name="cooking_time" value="under15"> Under 15 min</label>
                                    <label><input type="radio" name="cooking_time" value="under30"> Under 30 min</label>
                                    <label><input type="radio" name="cooking_time" value="30to60"> 30–60 min</label>
                                </div>
                            </div>

                            <div>
                                <label class="font-semibold">Budget Level</label>
                                <div class="flex flex-wrap gap-2 mt-1">
                                    <label><input type="radio" name="budget" value="any"> Any</label>
                                    <label><input type="radio" name="budget" value="budgetFriendly"> Budget-Friendly</label>
                                    <label><input type="radio" name="budget" value="premium"> Premium</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="w-full flex justify-end mt-4">
                        <button type="submit"
                            class="w-full sm:w-auto px-6 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600">
                            Generate Recipe
                        </button>
                    </div>
                </form>

                @php
                    $recentIngredients = $recentIngredients ?? collect();
                    $frequentIngredients = $frequentIngredients ?? collect();
                @endphp

                <div>
                    <h3 class="text-gray-700 font-medium">🕑 Recently Searched:</h3>
                    <div class="flex flex-wrap gap-2 mt-2 text-sm">
                       @foreach(array_unique(array_slice($recentIngredients, 0, 6)) as $index => $item)

                            <span class="bg-info-100 text-info-800 px-3 py-1 rounded-full text-sm cursor-pointer hover:bg-info-200 transition
                                {{ $index === 0 ? 'ring-2 ring-info-200 animate-pulse' : '' }}"
                                onclick="addToTagify('{{ $emojiMap[$item] ?? '' }} {{ $item }}')">
                                {{ $item }}
                                <button type="button" class="ml-1 text-danger-500 hover:text-danger-700"
                                    onclick="event.stopPropagation(); removeRecent('{{ $item }}')">✖</button>
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4">
                    <h5 class="font-semibold text-gray-700">🔥 Frequently Used:</h5>
                    <div class="flex flex-wrap gap-2 mt-1">
                        @foreach($frequentIngredients as $freq)
                            <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm">
                                {{ $freq->name }} ({{ $freq->total }})
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="mt-8 p-4 sm:p-6 bg-primary-100 rounded-lg shadow transition-transform hover:scale-105">
                    <div class="flex items-center mb-4">
                        <span class="text-2xl mr-2">🍲</span>
                        <h3 class="text-xl font-bold text-gray-800">Quick Tips</h3>
                    </div>
                    <ul class="list-disc list-inside text-gray-700 space-y-2">
                        <li class="cursor-pointer hover:underline" onclick="applyTip('chicken, rice, spinach')">
                            Type things like <span class="font-semibold">chicken</span>, <span class="font-semibold">rice</span>, or <span class="font-semibold">spinach</span> and explore Malaysian flavors!
                        </li>
                        <li class="cursor-pointer hover:underline" onclick="applyTip('tofu, spinach, garlic')">
                            Looking for vegetarian? Tofu + spinach + garlic works great!
                        </li>
                        <li class="cursor-pointer hover:underline" onclick="applyTip('chili, sambal')">
                            Want spicy? Add chili or sambal for a kick!
                        </li>
                    </ul>
                </div>
            </div>

            <div class="p-4 overflow-x-hidden">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-2 gap-4">
                    <img src="{{ asset('images/dish5.jpg') }}" class="col-span-2 rounded-xl shadow-md hover:scale-105 transition">
                    <img src="{{ asset('images/dish2.jpg') }}" class="rounded-xl shadow-md hover:scale-105 transition">
                    <img src="{{ asset('images/dish3.jpg') }}" class="rounded-xl shadow-md hover:scale-105 transition">
                    <img src="{{ asset('images/dish4.jpg') }}" class="rounded-xl shadow-md hover:scale-105 transition">
                    <img src="{{ asset('images/dish6.jpg') }}" class="rounded-xl shadow-md hover:scale-105 transition">
                </div>
            </div>
        </div>
    </div>

    <!-- Fullscreen Lottie Loading Spinner -->
    <div id="loadingSpinner" class="fixed inset-0 bg-white bg-opacity-80 flex flex-col items-center justify-center z-50 hidden">
        <dotlottie-player
            src="https://lottie.host/d76843dc-6d98-4611-9f25-e96ddceee97e/907T7zvjXw.json"
            background="transparent"
            speed="1"
            style="width: 300px; height: 300px;"
            loop
            autoplay>
        </dotlottie-player>
        <p class="mt-4 text-lg font-semibold text-gray-700 animate-pulse">
            Generating recipes... Please wait!
        </p>
    </div>

</x-app-layout>

<!-- Tagify Assets -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css">
<script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>

<script>
    function applyTip(text) {
        document.querySelector('input[name=ingredients]').value = text;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var input = document.querySelector('input[name=ingredients]');

        window.tagify = new Tagify(input, {
            whitelist: [],
            enforceWhitelist: false,
            transformTag: transformTag, // ✅ Tagify-native styling
            dropdown: {
                enabled: 1,
                maxItems: 10,
                classname: 'tags-look tagify-tailwind-dropdown',
                fuzzySearch: true,
                position: 'text',
                highlightFirst: true
            }
        });

        fetch('/api/ingredients')
            .then(res => res.json())
            .then(function (ingredientList) {
                tagify.settings.whitelist = ingredientList;
            })
            .catch(function (error) {
                console.error('Error fetching ingredients:', error);
            });

        // ✅ Random pastel HSL generator
        function getRandomColor() {
            function rand(min, max) {
                return min + Math.random() * (max - min);
            }

            var h = rand(1, 360) | 0,
                s = rand(25, 45) | 0,
                l = rand(85, 92) | 0;

            return 'hsl(' + h + ',' + s + '%,' + l + '%)';
        }

        // ✅ Tagify transform hook
        function transformTag(tagData) {
            tagData.style = "--tag-bg:" + getRandomColor();
        }
    });

    function addToTagify(item) {
        if (window.tagify) {
            window.tagify.addTags([{ value: item }]);
        }
    }

    function removeRecent(name) {
        document.querySelectorAll('.text-info-800').forEach(el => {
            if (el.textContent.includes(name)) {
                el.remove();
            }
        });
    }

    function showLoading() {
        document.getElementById('loadingSpinner').classList.remove('hidden');
    }
</script>
