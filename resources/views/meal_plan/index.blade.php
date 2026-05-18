<x-app-layout>
    <x-slot name="header">
        
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Meal Plans') }}
        </h2>
    </x-slot>

    <div class="text-center max-w-4xl mx-auto py-8 px-4">

        <form method="GET" action="{{ route('meal-plan.index') }}">
            <div class="flex items-center gap-3 max-w-md w-full">
                <label for="date" class="text-sm font-medium text-gray-700 whitespace-nowrap flex items-center">
                    📅 Select Date
                </label>

                <div class="relative w-[200px]">
                    <select name="date" id="date" onchange="this.form.submit()"
                        class="appearance-none w-full border border-gray-300 rounded-md py-2 pl-3 pr-10 text-xs shadow-sm focus:ring-2 focus:ring-danger-300 focus:border-danger-300">
                        @foreach ($availableDates as $availableDate)
                            <option value="{{ $availableDate }}" {{ $date == $availableDate ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::parse($availableDate)->format('l, d M Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>


        @if ($plans->isNotEmpty())
            @php
                $categories = ['breakfast' => '🍳 Breakfast', 'lunch' => '🍛 Lunch', 'dinner' => '🍽 Dinner', 'snack' => '🍪 Snacks'];
            @endphp

            @foreach ($categories as $key => $label)
                @php $mealsByType = $plans[$key] ?? collect(); @endphp

                @if ($mealsByType->isNotEmpty())
                    <div class="text-left mb-4 mt-10">
                        <h3 class="text-xl font-semibold text-gray-800">{{ $label }}</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                        @foreach ($mealsByType as $meal)
                            @php $recipe = $meal->recipe; @endphp

                            <div class="relative rounded overflow-hidden shadow-lg flex flex-col bg-white">

                                <!-- 3 Dots Menu -->
                                <div class="absolute top-2 right-2 z-10">
                                    <button type="button" onclick="toggleMenu('menu-{{ $meal->id }}')"
                                        class="bg-white text-gray-700 rounded-full w-8 h-8 flex items-center justify-center shadow hover:bg-gray-100 focus:outline-none">
                                        &#8942;
                                    </button>
                                    <div id="menu-{{ $meal->id }}"
                                        class="hidden absolute right-0 mt-2 w-40 bg-white border rounded shadow-md text-sm">
                                        <!--  EDIT opens modal -->
                                        <button onclick="openModal({{ $meal->id }})"
                                            class="block w-full text-left px-4 py-2 hover:bg-gray-100">
                                            Edit
                                        </button>
                                        <button type="button" onclick="confirmDelete({{ $meal->id }})"
                                            class="w-full text-left px-4 py-2 text-danger-600 hover:bg-danger-100">
                                            Delete
                                        </button>

                                        <form id="delete-form-{{ $meal->id }}" action="{{ route('meal-plan.destroy', $meal->id) }}"
                                            method="POST" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </div>

                                <!-- Card Content -->
                                <a href="{{ route('recipe.detail', $recipe->id) }}">
                                    <div class="relative h-48 w-full overflow-hidden">
                                        <img src="{{ asset($recipe->image) }}" alt="recipe image" onerror="this.onerror=null; this.src='/images/placeholder.jpg';" class="w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-black bg-opacity-20 hover:bg-opacity-5 transition duration-300">
                                        </div>
                                    </div>
                                </a>
                                <div class="px-6 py-4 mb-auto">
                                    <a href="{{ route('recipe.detail', $recipe->id) }}"
                                        class="font-medium text-lg inline-block hover:text-primary-600 transition mb-2">
                                        {{ $recipe->name }}
                                    </a>
                                    <p class="text-gray-500 text-sm">{{ $recipe->description }}</p>
                                </div>
                                <div class="px-6 py-3 flex flex-wrap gap-3 justify-between bg-gray-100 text-xs text-gray-900">
                                    <span>📅 {{ \Carbon\Carbon::parse($meal->date)->format('d M Y') }}</span>
                                    <span>🍽 {{ ucfirst($meal->meal_type) }}</span>
                                    <span>🔥 {{ $recipe->calories ?? 'N/A' }} kcal</span>
                                </div>
                            </div>
                            <!-- Edit Modal -->
                            <div id="edit-modal-{{ $meal->id }}"
                                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden"
                                onclick="closeModalOnOutside(event, {{ $meal->id }})">

                                <div class="bg-white w-full max-w-md mx-auto p-6 rounded shadow-lg">
                                    <h2 class="text-lg font-semibold mb-4">✏️ Edit Meal</h2>
                                    <form method="POST" action="{{ route('meal-plan.update', $meal->id) }}">
                                        @csrf
                                        @method('PUT')

                                        <div class="mb-4 text-left">
                                            <label for="date-{{ $meal->id }}" class="block text-sm font-medium text-gray-700">📅
                                                Date</label>
                                            <input type="date" name="date" id="date-{{ $meal->id }}" value="{{ $meal->date }}"
                                                class="mt-1 block w-full rounded border-gray-300 shadow-sm" required>
                                        </div>

                                        <div class="mb-4 text-left">
                                            <label for="meal_type-{{ $meal->id }}" class="block text-sm font-medium text-gray-700">🍽
                                                Meal Type</label>
                                            <select name="meal_type" id="meal_type-{{ $meal->id }}"
                                                class="mt-1 block w-full rounded border-gray-300 shadow-sm" required>
                                                <option value="breakfast" {{ $meal->meal_type === 'breakfast' ? 'selected' : '' }}>
                                                    Breakfast</option>
                                                <option value="lunch" {{ $meal->meal_type === 'lunch' ? 'selected' : '' }}>Lunch</option>
                                                <option value="dinner" {{ $meal->meal_type === 'dinner' ? 'selected' : '' }}>Dinner
                                                </option>
                                                <option value="snack" {{ $meal->meal_type === 'snack' ? 'selected' : '' }}>Snack
                                                </option>
                                            </select>
                                        </div>

                                        <div class="flex justify-end gap-2">
                                            <button type="button" onclick="closeModal({{ $meal->id }})"
                                                class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded">Cancel</button>
                                            <button type="submit"
                                                class="bg-info-500 hover:bg-info-600 text-white px-4 py-2 rounded">Update</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach

        @else
            <div class="h-[60vh] flex items-center justify-center">
                <div class="text-gray-600 px-4 py-6 rounded text-center max-w-xl">
                    <h2 class="text-xl mb-3">😴 No meals planned for this date.</h2>
                    <p class="text-base mb-5">Let’s fix that by generating something delicious.</p>
                    <a href="{{ route('generate') }}"
                        class="inline-block bg-danger-500 hover:bg-danger-600 text-white text-sm px-6 py-2 rounded shadow transition-transform hover:scale-105">
                        🍳 Generate Recipe
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- JavaScript -->
    <script>
        function toggleMenu(id) {
            document.querySelectorAll('[id^="menu-"]').forEach(menu => {
                if (menu.id !== id) menu.classList.add('hidden');
            });
            document.getElementById(id).classList.toggle('hidden');
        }

        function confirmDelete(mealId) {
            if (confirm("Are you sure you want to delete this meal?")) {
                document.getElementById('delete-form-' + mealId).submit();
            }
        }

        function openModal(id) {
            document.getElementById('edit-modal-' + id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById('edit-modal-' + id).classList.add('hidden');
        }

        window.addEventListener('click', function (e) {
            const target = e.target;
            document.querySelectorAll('[id^="menu-"]').forEach(menu => {
                if (!menu.contains(target) && !menu.previousElementSibling.contains(target)) {
                    menu.classList.add('hidden');
                }
            });
        });

        function closeModalOnOutside(e, id) {
            const modalBox = document.querySelector('#edit-modal-' + id + ' > div');
            if (!modalBox.contains(e.target)) {
                closeModal(id);
            }
        }

    </script>

</x-app-layout>