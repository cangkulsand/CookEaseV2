<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-center">A bit Information About You</h2>
    </x-slot>

    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white shadow-lg rounded-2xl flex flex-col md:w-1/2 p-8 md:p-14 max-w-lg w-full">

            <!-- Welcome Message -->
            <div class="text-center cursor-default mb-6">
                <div class="text-2xl text-danger-600 font-semibold mb-1">Complete Your Profile 👋</div>
                <div class="text-sm text-gray-500 mb-6">- Enter Your BMI Information -</div>
            </div>

            <form method="POST" action="{{ route('profile.bmi.update') }}">
                @csrf

                <!-- Age -->
                <div class="mb-4">
                    <x-input-label for="age" :value="__('Age')" />
                    <x-text-input id="age" class="block mt-1 w-full" type="number" name="age" required autofocus />
                    <x-input-error :messages="$errors->get('age')" class="mt-2" />
                </div>

                <!-- Gender -->
                <div class="mb-4">
                    <x-input-label for="gender" :value="__('Gender')" />
                    <select name="gender" id="gender" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                        <option value="">-- Select Gender --</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                    <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                </div>

                <!-- Height -->
                <div class="mb-4">
                    <x-input-label for="height" :value="__('Height (cm)')" />
                    <x-text-input id="height" class="block mt-1 w-full" type="number" name="height" step="0.01" required />
                    <x-input-error :messages="$errors->get('height')" class="mt-2" />
                </div>

                <!-- Weight -->
                <div class="mb-4">
                    <x-input-label for="weight" :value="__('Weight (kg)')" />
                    <x-text-input id="weight" class="block mt-1 w-full" type="number" name="weight" step="0.01" required />
                    <x-input-error :messages="$errors->get('weight')" class="mt-2" />
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-primary-400 text-black font-semibold px-6 py-2 rounded hover:bg-primary-500 transition">
                    Next
                </button>
            </form>

        </div>
    </div>
</x-app-layout>