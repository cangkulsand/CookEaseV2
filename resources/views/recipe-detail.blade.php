<x-app-layout>
    <x-slot name="header">
        
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ $recipe['name'] }}
    </h2>
    </x-slot>

    @if (session('message'))
        <div 
            x-data="{ show: true }" 
            x-init="setTimeout(() => show = false, 3000)" 
            x-show="show" 
            class="max-w-4xl mx-auto mt-6">
            <div class="bg-primary-100 border border-primary-400 text-primary-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline">{{ session('message') }}</span>
            </div>
        </div>
    @endif

    <div class="py-10 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
        <div class="bg-white shadow rounded-xl p-6">
            <img src="{{ asset($recipe['image']) }}"
            onerror="this.onerror=null; this.src='/images/placeholder.jpg';"
            alt="recipe image"
            class="w-full h-64 object-cover rounded mb-6">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm text-gray-600 mb-8">
                <p><strong>⏱ Duration:</strong> {{ $recipe['duration'] }}</p>
                <p><strong>🍽 Servings:</strong> {{ $recipe['servings'] }}</p>
                <p><strong>📊 Difficulty:</strong> {{ ucfirst($recipe['difficulty']) }}</p>
                <p><strong>🔥 Calories:</strong> {{ $recipe['calories'] ?? 'N/A' }} kcal</p>
            </div>

            <h3 class="text-lg font-semibold mb-2">📝 Description</h3>
            <p class="text-gray-700 mb-4">{{ $recipe['description'] }}</p>

           <h3 class="text-lg font-semibold mb-2">🥗 Ingredients</h3>
            <ul class="list-disc pl-5 text-gray-700 mb-4">
                @php
                    $ingredients = is_array($recipe['ingredients'])
                        ? $recipe['ingredients']
                        : json_decode($recipe['ingredients'], true) ?? [];
                @endphp

                @foreach ($ingredients as $ingredient)
                    <li>{{ is_array($ingredient) ? implode(' ', $ingredient) : $ingredient }}</li>
                @endforeach
            </ul>

            <h3 class="text-lg font-semibold">👨‍🍳 Instructions</h3>
            <p class="text-gray-700 whitespace-pre-line">
                {{ is_array($recipe['instructions']) ? implode("\n", $recipe['instructions']) : $recipe['instructions'] }}
            </p>

            <div class="mt-8 flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2 space-y-2 sm:space-y-0">
                <div class="flex flex-col sm:flex-row sm:space-x-2 space-y-2 sm:space-y-0">
                    <!-- Hidden textarea for copying -->
                    <textarea id="groceryListText" class="hidden"></textarea>

                    <!-- Copy to Clipboard Confirmation -->
                    <div id="copyMessage" class="hidden text-primary-600 mt-2 font-medium text-center">Copied to clipboard! ✅</div>
                    
                    <!-- 🛒 Dropdown Grocery List Actions -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="text-sm bg-primary-500 text-white px-4 py-2 rounded hover:bg-primary-600 w-full sm:w-auto">
                            🛒 Generate Shopping List
                        </button>

                        <div x-show="open" @click.away="open = false" class="absolute z-10 mt-2 w-56 bg-white rounded shadow w-full sm:w-auto">
                            <button onclick="printGroceryList()" class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100">🖨️ Print</button>
                            <button onclick="copyGroceryList()" class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100">📋 Copy to Clipboard</button>
                            <a href="https://wa.me/?text={{ urlencode('🛒 CookEase Shopping List:' . "\n- " . implode("\n- ", is_array($recipe['groceryLists']) ? $recipe['groceryLists'] : json_decode($recipe['groceryLists'] ?? '[]', true))) }}"
                                target="_blank"
                                class="block px-4 py-2 text-sm hover:bg-gray-100">
                                📤 Share on WhatsApp
                            </a>
                        </div>
                    </div>

                    <!-- Save / Unsave -->
                    @if ($isSession)
                        <form method="POST" action="{{ route('recipe.save') }}">
                            @csrf
                            {{-- 👇 Add this to detect session vs DB --}}
                        <input type="hidden" name="from" value="{{ $isSession ? 'session' : 'db' }}">
                        <input type="hidden" name="index" value="{{ request()->route('index') }}">

                        {{-- recipe data --}}
                        <input type="hidden" name="name" value="{{ $recipe['name'] }}">
                            <input type="hidden" name="description" value="{{ $recipe['description'] }}">
                            <input type="hidden" name="duration" value="{{ $recipe['duration'] }}">
                            <input type="hidden" name="servings" value="{{ $recipe['servings'] }}">
                            <input type="hidden" name="difficulty" value="{{ $recipe['difficulty'] }}">
                            <input type="hidden" name="calories" value="{{ $recipe['calories'] }}">
                            <input type="hidden" name="image" value="{{ $recipe['image'] }}">
                            <input type="hidden" name="instructions" value="{{ is_array($recipe['instructions']) ? implode("\n", $recipe['instructions']) : $recipe['instructions'] }}">
                            <input type="hidden" name="ingredients" value="{{ json_encode($recipe['ingredients']) }}">
                            <input type="hidden" name="groceryLists" value="{{ json_encode($recipe['groceryLists']) }}">
                            <button type="submit" class="text-sm bg-danger-500 text-white px-4 py-2 rounded hover:bg-danger-600 w-full sm:w-auto">🤍 Save</button>
                        </form>
                    @else
                        @if ($isFavorited && isset($recipeId))
                            <form method="POST" action="{{ route('recipe.unsave', $recipeId) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 w-full sm:w-auto">💔 Unsave</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('recipe.save') }}">
                                @csrf
                                <input type="hidden" name="from" value="{{ request()->query('from') }}">
                                <input type="hidden" name="name" value="{{ $recipe['name'] }}">
                                <input type="hidden" name="description" value="{{ $recipe['description'] }}">
                                <input type="hidden" name="duration" value="{{ $recipe['duration'] }}">
                                <input type="hidden" name="servings" value="{{ $recipe['servings'] }}">
                                <input type="hidden" name="difficulty" value="{{ $recipe['difficulty'] }}">
                                <input type="hidden" name="calories" value="{{ $recipe['calories'] }}">
                                <input type="hidden" name="image" value="{{ $recipe['image'] }}">
                                <input type="hidden" name="instructions" value="{{ $recipe['instructions'] }}">
                                <input type="hidden" name="ingredients" value="{{ json_encode($recipe['ingredients']) }}">
                                <input type="hidden" name="groceryLists" value="{{ json_encode($recipe['groceryLists']) }}">
                                <button type="submit" class="text-sm bg-danger-500 text-white px-4 py-2 rounded hover:bg-danger-600 w-full sm:w-auto">🤍 Save</button>
                            </form>
                        @endif
                    @endif

                    <!-- Add to Plan Button -->
                    @if ($isPlanned ?? false)
                        <a href="{{ route('meal-plan.index') }}"
                            class="text-sm bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-200 text-center w-full sm:w-auto">
                            📅 View Meal Plans
                        </a>
                    @else
                        <button type="button"
                            onclick="document.getElementById('addToPlanModal').classList.remove('hidden')"
                            class="text-sm bg-info-500 text-white px-4 py-2 rounded hover:bg-info-600 w-full sm:w-auto">
                            📅 Add to Meal Plan
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Printable Section -->
    <div id="printableGroceryList" class="hidden print:block p-6 max-w-md mx-auto bg-white">
        <h2 class="text-xl font-bold mb-4">🛒 Grocery Shopping List</h2>
        <ul class="list-disc pl-6 text-gray-800 space-y-1">
           <ul class="list-disc pl-6 text-gray-800 space-y-1">
            @foreach (is_array($recipe['groceryLists']) ? $recipe['groceryLists'] : json_decode($recipe['groceryLists'], true) as $item)
                <li>{{ ucfirst($item) }}</li>
            @endforeach
            </ul>
        </ul>
        <p class="mt-6 text-sm text-gray-500">Generated by CookEase - {{ now()->toDateString() }}</p>
    </div>

    <!-- Add to Plan Modal -->
    <div id="addToPlanModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-6 rounded shadow-lg w-full max-w-md relative">
            <button onclick="document.getElementById('addToPlanModal').classList.add('hidden')"
                class="absolute top-2 right-2 text-gray-400 hover:text-danger-600 text-xl">&times;</button>

            <h2 class="text-lg font-semibold text-gray-800 mb-4">Add This Recipe to Your Meal Plan</h2>

            <form method="POST" action="{{ route('meal-plan.add') }}">
                @csrf
                <input type="hidden" name="name" value="{{ $recipe['name'] }}">
                <input type="hidden" name="description" value="{{ $recipe['description'] }}">
                <input type="hidden" name="duration" value="{{ $recipe['duration'] }}">
                <input type="hidden" name="servings" value="{{ $recipe['servings'] }}">
                <input type="hidden" name="difficulty" value="{{ $recipe['difficulty'] }}">
                <input type="hidden" name="calories" value="{{ $recipe['calories'] }}">
                <input type="hidden" name="image" value="{{ $recipe['image'] }}">
                <input type="hidden" name="instructions" value="{{ $recipe['instructions'] }}">
                <input type="hidden" name="ingredients" value="{{ json_encode($recipe['ingredients']) }}">
                <input type="hidden" name="groceryLists" value="{{ json_encode($recipe['groceryLists']) }}">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Meal Type</label>
                    <select name="meal_type" class="w-full border rounded px-3 py-2" required>
                        <option value="">-- Select Meal Type --</option>
                        <option value="breakfast">Breakfast</option>
                        <option value="lunch">Lunch</option>
                        <option value="dinner">Dinner</option>
                        <option value="snack">Snacks</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" name="date" class="w-full border rounded px-3 py-2" required>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded text-sm">Add to plan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Success & Error Toasts -->
    <div id="successMessage"
        class="fixed bottom-6 right-6 bg-primary-100 border border-primary-300 text-primary-800 px-6 py-4 rounded-lg shadow-lg z-50 hidden">
        <div class="flex items-center justify-between">
            <div>
                <p class="font-semibold">Meal plan added successfully.</p>
                <p class="text-sm text-gray-700 mt-1">You can now view your updated meal plan.</p>
            </div>
            <button onclick="document.getElementById('successMessage').classList.add('hidden')"
                class="text-danger-500 hover:text-danger-700 text-lg font-bold leading-none">&times;</button>
        </div>
    </div>

    <div id="errorMessage"
        class="fixed bottom-6 right-6 bg-danger-100 border border-danger-300 text-danger-800 px-6 py-4 rounded-lg shadow-lg z-50 hidden">
        <div class="flex items-center justify-between">
            <div>
                <p class="font-semibold">Failed to add meal plan.</p>
                <p id="errorDetail" class="text-sm text-gray-700 mt-1">Something went wrong.</p>
            </div>
            <button onclick="document.getElementById('errorMessage').classList.add('hidden')"
                class="text-danger-500 hover:text-danger-700 text-lg font-bold leading-none">&times;</button>
        </div>
    </div>

     <!-- Review Section -->
    @if (!$isSession)
    <div class="max-w-4xl mx-auto mt-10">
        <div class="bg-white shadow rounded-xl p-6">
            <h3 class="text-lg font-semibold mb-4">⭐ User Reviews</h3>

            <!-- Leave a Review -->
            @auth
                @if (!$isReviewed)
                    <form action="{{ route('review.store') }}" method="POST" class="mb-6">
                        @csrf
                        <input type="hidden" name="recipe_id" value="{{ $recipeId ?? $recipe['id'] ?? null }}">

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Your Rating</label>
                            <select name="rating" class="w-full border rounded px-3 py-2" required>
                                <option value="">-- Select Rating --</option>
                                @for ($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}">{{ $i }} Star{{ $i > 1 ? 's' : '' }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Your Comment</label>
                            <textarea name="comment" class="w-full border rounded px-3 py-2" rows="3" required></textarea>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="bg-info-600 text-white px-4 py-2 rounded hover:bg-info-700">Submit Review</button>
                        </div>
                    </form>
                @else
                    <p class="text-gray-600 mb-6">✅ You've already submitted a review for this recipe.</p>
                @endif
            @else
                <p class="text-gray-600 mb-6">Please <a href="{{ route('login') }}" class="text-info-500 hover:underline">login</a> to leave a review.</p>
            @endauth

            <!-- Display Reviews -->
            @forelse ($reviews as $review)
                <div class="border-t border-gray-200 pt-4 mt-4">
                    <div class="flex justify-between items-center mb-2">
                        <div class="text-sm font-semibold text-gray-800">{{ $review->user->name }}</div>
                        <div class="text-primary-500 text-sm">
                            @for ($i = 0; $i < $review->rating; $i++)
                                ⭐
                            @endfor
                        </div>
                    </div>
                    <p class="text-gray-700">{{ $review->comment }}</p>
                    <p class="text-gray-400 text-xs mt-1">Posted on {{ $review->created_at->format('F j, Y') }}</p>
                </div>
            @empty
                <p class="text-gray-600">No reviews yet. Be the first to leave one!</p>
            @endforelse
        </div>
    </div>
    @else
        <div class="max-w-4xl mx-auto mt-10 bg-primary-50 text-primary-800 border border-primary-200 rounded-xl p-6">
            <p class="text-sm">✨ This is a newly generated recipe. Please save it first to unlock review features.</p>
        </div>
    @endif

    <!-- Scripts -->
    <script>
        function printGroceryList() {
            const printable = document.getElementById('printableGroceryList');
            printable.classList.remove('hidden');
            window.print();
            setTimeout(() => printable.classList.add('hidden'), 100);
        }

        function copyGroceryList() {
            let groceryItems = @json($recipe['groceryLists']);

            // If it's a string, try to parse it
            if (typeof groceryItems === 'string') {
                try {
                    groceryItems = JSON.parse(groceryItems);
                } catch (e) {
                    console.error('Invalid JSON for groceryItems');
                    groceryItems = [];
                }
            }

            const groceryText = "CookEase Shopping List:\n\n" + groceryItems.map(item => "- " + item).join("\n");

            navigator.clipboard.writeText(groceryText)
                .then(() => {
                    const message = document.getElementById('copyMessage');
                    message.classList.remove('hidden');
                    setTimeout(() => message.classList.add('hidden'), 2000);
                })
                .catch(err => {
                    console.error('Clipboard error:', err);
                    alert('Failed to copy to clipboard.');
                });
        }

    </script>

</x-app-layout>
