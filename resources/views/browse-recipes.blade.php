<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Browse Recipes') }}</h2>
    </x-slot>

    <div class="py-12 max-w-7xl mx-auto px-6">

        <h3 class="text-xl font-bold mb-6">All Recipes</h3>

        <form method="GET" action="{{ route('recipes.browse') }}"
            class="mb-6 space-y-4 md:space-y-0 md:flex md:flex-wrap md:items-center md:space-x-4">
            
            <input type="text" name="search" placeholder="Search recipes..." value="{{ request('search') }}"
                class="border border-gray-300 rounded px-4 py-2 w-full md:w-auto flex-1 min-w-[200px]">

            <select name="difficulty"
                class="border border-gray-300 rounded px-4 py-2 w-full md:w-48">
                <option value="">All Difficulties</option>
                <option value="easy" {{ request('difficulty') == 'easy' ? 'selected' : '' }}>Easy</option>
                <option value="medium" {{ request('difficulty') == 'medium' ? 'selected' : '' }}>Medium</option>
                <option value="hard" {{ request('difficulty') == 'hard' ? 'selected' : '' }}>Hard</option>
            </select>

            <input type="number" name="calories_min" placeholder="Min Calories" value="{{ request('calories_min') }}"
                class="border border-gray-300 rounded px-4 py-2 w-full md:w-28">

            <input type="number" name="calories_max" placeholder="Max Calories" value="{{ request('calories_max') }}"
                class="border border-gray-300 rounded px-4 py-2 w-full md:w-28">

            <button type="submit"
                class="bg-primary-500 text-white px-4 py-2 rounded w-full md:w-auto hover:bg-primary-600">
                <i class="fa-solid fa-magnifying-glass fa-sm"></i> Filter
            </button>
        </form>


        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            @foreach ($recipes as $recipe)
                <!-- Make the outer card a flex container with full height -->
                <div class="bg-white rounded-xl overflow-hidden shadow hover:shadow-lg transition duration-300 flex flex-col h-full">
                    <a href="{{ route('recipe.detail', $recipe->id) }}" class="flex flex-col h-full">
                        <img src="{{ asset($recipe->image) }}" alt="{{ $recipe->name }}" onerror="this.onerror=null; this.src='/images/placeholder.jpg';" class="w-full h-48 object-cover">
                        
                        <!-- Card content grows to fill remaining space -->
                        <div class="p-4 flex flex-col flex-grow">
                            <h3 class="font-semibold text-lg text-gray-800">{{ $recipe->name }}</h3>
                            <p class="text-gray-500 text-sm mt-1">{{ Str::limit($recipe->description, 100) }}</p>

                            <!-- This section sticks to the bottom -->
                            <div class="mt-auto text-xs text-gray-600 flex justify-between pt-4">
                                <span>⏱ {{ $recipe->duration }}</span>
                                <span class="text-accent-500">🔥 {{ $recipe->calories }} kcal</span>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>


        <div class="mt-6">
            {{ $recipes->links() }}
        </div>
    </div>
</x-app-layout>
