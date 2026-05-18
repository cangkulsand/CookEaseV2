<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('My Saved Recipes') }}</h2>
    </x-slot>

    <div class="py-12 max-w-7xl mx-auto px-6">
        @if ($favorites->isEmpty())
            <p class="text-gray-500">You haven't saved any recipes yet.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                @foreach ($favorites as $favorite)
                <div class="bg-white rounded-xl overflow-hidden shadow hover:shadow-lg transition duration-300 flex flex-col h-full">
                    <a href="{{ route('recipe.detail', $favorite->recipe->id) }}" class="flex flex-col h-full">
                        <img src="{{ asset($favorite->recipe->image) }}" alt="{{ $favorite->recipe->name }}" onerror="this.onerror=null; this.src='/images/placeholder.jpg';" class="w-full h-48 object-cover">
                        
                        <!-- Card content grows to fill remaining space -->
                        <div class="p-4 flex flex-col flex-grow">
                            <h3 class="font-semibold text-lg text-gray-800">{{ $favorite->recipe->name }}</h3>
                            <p class="text-gray-500 text-sm mt-1">{{ Str::limit($favorite->recipe->description, 100) }}</p>

                            <!-- This section sticks to the bottom -->
                            <div class="mt-auto text-xs text-gray-600 flex justify-between pt-4">
                                <span>⏱ {{ $favorite->recipe->duration }}</span>
                                <span class="text-accent-500">🔥 {{ $favorite->recipe->calories }} kcal</span>
                            </div>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $favorites->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
