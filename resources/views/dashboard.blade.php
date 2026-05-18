@php use Illuminate\Support\Str; @endphp


<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-black leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4">
                    <p class="text-black text-2xl sm:text-3xl text-primary-600 font-bold">Hello,
                        {{ Auth::user()->name }}!</p>
                    <p class="text-gray-600 text-base sm:text-lg">Ready to cook something delicious?</p>
                </div>
            </div>
        </div>

        <div class="max-w-screen-xl mx-auto p-5 sm:p-10 md:p-10">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 px-4 sm:px-8 mb-10">
                <div class="flex items-center bg-white border rounded-sm overflow-hidden shadow">
                    <div class="p-4 text-white" style="background-color: #FAD59A"><i
                            class="fa-solid fa-weight-scale fa-3x"></i></div>
                    <div class="px-4 text-gray-700">
                        <h3 class="text-sm tracking-wider">Your BMI</h3>
                        <!-- <p class="text-2xl">{{ $bmiCategory }}</p> -->
                        <p class="text-2xl">{{ $bmiCategory ?? 'Unknown' }}</p>
                    </div>
                </div>
                <div class="flex items-center bg-white border rounded-sm overflow-hidden shadow">
                    <div class="p-4 text-white" style="background-color: #FADA7A"><i class="fa-solid fa-fire fa-3x"></i>
                    </div>
                    <div class="px-4 text-gray-700">
                        <h3 class="text-sm tracking-wider">Calories Intake Today</h3>
                        <!-- <p class="text-2xl">{{ $todayCalories }} kcal</p> -->
                        <p class="text-2xl">{{ $todayCalories ?? 0 }} kcal</p>

                    </div>
                </div>
                <div class="flex items-center bg-white border rounded-sm overflow-hidden shadow">
                    <div class="p-4 text-white" style="background-color: #B1C29E"><i
                            class="fa-solid fa-bowl-rice fa-3x"></i></div>
                    <div class="px-4 text-gray-700">
                        <h3 class="text-sm tracking-wider">Saved Recipes</h3>
                        <!-- <p class="text-2xl">{{ $savedCount }}</p> -->
                        <p class="text-2xl">{{ $savedCount ?? 0 }}</p>

                    </div>
                </div>
                <div class="flex items-center bg-white border rounded-sm overflow-hidden shadow">
                    <div class="p-4 text-white" style="background-color: #F0A04B"><i
                            class="fa-solid fa-table-list fa-3x"></i></div>
                    <div class="px-4 text-gray-700">
                        <h3 class="text-sm tracking-wider">Meal Plans this Week</h3>
                        <!-- <p class="text-2xl">{{ $weeklyPlans }}</p> -->
                        <p class="text-2xl">{{ $weeklyPlans ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-6">
                <!-- Left Column -->
                <div class="flex flex-col space-y-6">

                    <!-- Daily Cooking Tips -->
                    <div class="bg-primary-100 shadow rounded-2xl p-4 flex flex-col h-full">
                        <h2 class="text-lg sm:text-xl md:text-xl font-semibold mb-4">💡Daily Cooking Tips</h2>

                        @if ($apiError)
                            <div class="text-danger-600 text-sm">⚠️ Failed to load tips from AI. Showing default tips.</div>
                        @endif

                        <ul class="space-y-2 list-disc list-inside text-sm text-gray-700 flex-1 mt-2">
                            @forelse ($tips as $tip)
                                <li>{{ $tip }}</li>
                            @empty
                                <li>No tips available.</li>
                            @endforelse
                        </ul>
                    </div>

                    <!-- Today's Meal Plan -->
                    <div class="bg-primary-50 shadow rounded-2xl p-4 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg sm:text-xl md:text-xl font-semibold">📆 Today's Meal Plan</h2>
                            <a href="{{ route('meal-plan.index') }}"
                                class="text-sm sm:text-base md:text-base text-info-600 hover:underline">View All</a>
                        </div>

                        <ul class="space-y-3 flex-1">
                            @php
                                $icons = [
                                    'breakfast' => '🌞',
                                    'lunch' => '🥗',
                                    'dinner' => '🌙',
                                    'snack' => '🍪',
                                ];
                            @endphp

                            @forelse ($todaysPlans as $plan)
                                @if ($plan->recipe)
                                    <a href="{{ route('recipe.detail', $plan->recipe->id) }}" class="block">
                                        <li
                                            class="p-4 bg-white border border-primary-200 backdrop-blur-md rounded-xl shadow hover:scale-[1.01] hover:shadow-md transition-all duration-200 cursor-pointer">
                                            <div class="flex items-center gap-3 text-gray-800">
                                                <span class="text-2xl">{{ $icons[$plan->meal_type] ?? '🍴' }}</span>
                                                <div class="text-sm sm:text-base">
                                                    <span class="font-semibold capitalize">{{ $plan->meal_type }}:</span>
                                                    <span>{{ $plan->recipe->name }}</span>
                                                </div>
                                            </div>
                                        </li>
                                    </a>
                                @endif
                            @empty
                                <li class="text-gray-400 italic">No meals planned for today. ✨</li>
                            @endforelse
                        </ul>
                    </div>

                    <!-- Recently Saved Recipes -->
                    <div class="bg-accent-100 shadow rounded-2xl p-4 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg sm:text-xl md:text-xl font-semibold">❤️ Recently Saved Recipes</h2>
                            <a href="{{ route('recipes.saved') }}"
                                class="text-sm sm:text-base md:text-base text-info-600 hover:underline">View All</a>
                        </div>
                        <div class="space-y-3 flex-1">
                            @forelse ($recentFavorites as $recipe)
                                <div
                                    class="bg-white rounded-xl overflow-hidden shadow hover:shadow-lg transition duration-300">
                                    <a href="{{ route('recipe.detail', $recipe->id) }}">
                                        <img src="{{ asset($recipe->image) }}" alt="{{ $recipe->name }}"
                                            onerror="this.onerror=null; this.src='/images/placeholder.jpg';"
                                            class="w-full h-40 sm:h-48 md:h-56 object-cover">
                                        <div class="p-4">
                                            <h3 class="font-semibold text-lg text-gray-800">{{ $recipe->name }}</h3>
                                            <p class="text-gray-500 text-sm mt-1">
                                                {{ Str::limit($recipe->description, 100) }}</p>
                                            <div class="mt-2 text-xs text-gray-600 flex justify-between">
                                                <!-- <span>⏱ {{ $recipe->duration }}</span>
                                                <span>🔥 {{ $recipe->calories }} kcal</span> -->
                                                <span>⏱ {{ $recipe->duration ?? 'N/A' }}</span>
                                                <span>🔥 {{ $recipe->calories ?? 'N/A' }} kcal</span>

                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @empty
                                <p class="text-gray-400 italic">No saved recipes. ✨</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="bg-accent-50 shadow rounded-2xl p-4 flex flex-col h-full">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-lg sm:text-xl md:text-xl font-semibold">🥗 Recipe Recommendations</h2>
                        <a href="{{ route('recipes.browse') }}"
                            class="text-sm sm:text-base md:text-base text-info-600 hover:underline">View All</a>
                    </div>
                    <div class="space-y-3 flex-1">
                        @foreach ($recommendedRecipes as $recipe)
                            <div class="bg-white rounded-xl overflow-hidden shadow hover:shadow-lg transition duration-300">
                                <a href="{{ route('recipe.detail', $recipe->id) }}">
                                    <img src="{{ asset($recipe->image) }}" alt="{{ $recipe->name }}"
                                        onerror="this.onerror=null; this.src='/images/placeholder.jpg';"
                                        class="w-full h-40 sm:h-48 md:h-56 object-cover">
                                    <div class="p-4">
                                        <h3 class="font-semibold text-lg text-gray-800">{{ $recipe->name }}</h3>
                                        <p class="text-gray-500 text-sm mt-1">{{ Str::limit($recipe->description, 100) }}
                                        </p>
                                        <div class="mt-2 text-xs text-gray-600 flex justify-between">
                                            <span>⏱ {{ $recipe->duration }}</span>
                                            <span>🔥 {{ $recipe->calories }} kcal</span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>