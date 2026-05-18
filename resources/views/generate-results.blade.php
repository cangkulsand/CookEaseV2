<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Generated Recipes') }}
        </h2>
    </x-slot>

    <div class="max-w-screen-xl mx-auto p-5 sm:p-10 md:p-16">

        @if (isset($recipes) && is_array($recipes) && count($recipes) > 0)
            @php $totalRecipes = count($recipes); @endphp

            <div x-data="{ visible: 3 }">
                <!-- Recipe Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-10">
                    @foreach ($recipes as $index => $recipe)
                        <div x-show="{{ $index }} < visible" class="rounded overflow-hidden shadow-lg flex flex-col">
                            <div class="relative h-48 w-full overflow-hidden">
                                <a href="{{ route('recipe.detail', ['index' => $index, 'from' => 'generation', 'id' => $generationId]) }}">
                                    <img 
                                        src="{{ $recipe['image'] }}" 
                                        alt="recipe image"
                                        class="w-full h-full object-cover"
                                    >
                                    <div class="absolute inset-0 bg-gray-900 bg-opacity-25 hover:bg-opacity-10 transition duration-300"></div>
                                </a>
                            </div>
                            <div class="px-6 py-4 mb-auto">
                                <a href="{{ route('recipe.detail', ['index' => $index, 'from' => 'generation', 'id' => $generationId]) }}"
                                   class="font-medium text-lg inline-block hover:text-primary-600 transition mb-2">
                                    {{ $recipe['name'] }}
                                </a>
                                <p class="text-gray-500 text-sm">{{ $recipe['description'] }}</p>
                            </div>
                            <div class="px-6 py-3 flex flex-row items-center justify-between bg-gray-100 text-xs text-gray-900">
                                <span class="flex items-center"><i class="fa-regular fa-clock mr-1"></i> {{ $recipe['duration'] }}</span>
                                <span class="flex items-center"><i class="fa-solid fa-chart-simple mr-1"></i> {{ ucfirst($recipe['difficulty']) }}</span>
                                <span class="flex items-center"><i class="fas fa-utensils mr-1"></i> {{ $recipe['servings'] }} servings</span>
                                <span class="flex items-center"><i class="fas fa-fire mr-1"></i>{{ $recipe['calories'] ?? 'N/A' }} kcal</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Show More Button -->
                <div class="flex justify-center mt-8" x-show="visible < {{ $totalRecipes }}">
                    <button 
                        @click="visible += 3" 
                        class="bg-primary-500 text-white px-4 py-2 rounded hover:bg-primary-600 transition"
                    >
                        Show More Recipes
                    </button>
                </div>
            </div>
        @else
            <p class="text-center text-gray-500 mt-8">No recipes available. Please try again.</p>
        @endif
    </div>
</x-app-layout>
