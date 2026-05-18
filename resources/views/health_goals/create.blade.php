<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-center">Choose Your Health Goal</h2>
    </x-slot>

    <div class="flex items-center justify-center py-12 px-4">
        <div class="bg-white shadow-lg rounded-2xl flex flex-col md:w-1/2 p-8 md:p-14 max-w-lg w-full">

            <!-- Welcome Message -->
            <div class="text-center cursor-default mb-6">
                <div class="text-2xl text-danger-600 font-semibold mb-1">Set Your Goal 🎯</div>
                <div class="text-sm text-gray-500 mb-6">- Select what you want to achieve -</div>
            </div>

            <form method="POST" action="{{ route('health_goals.store') }}">
                @csrf

                <!-- Goal Selection -->
                <div class="mb-4">
                    <x-input-label for="goal" :value="__('Health Goal')" />
                    <select name="goal" id="goal" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                        <option value="">-- Select Goal --</option>
                        <option value="lose_weight">Lose Weight</option>
                        <option value="gain_weight">Gain Weight</option>
                        <option value="maintain_weight">Maintain Weight</option>
                    </select>
                    <x-input-error :messages="$errors->get('goal')" class="mt-2" />
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-primary-400 text-black font-semibold px-6 py-2 rounded hover:bg-primary-500 transition">
                    Save & Continue
                </button>
            </form>

        </div>
    </div>
</x-app-layout>
