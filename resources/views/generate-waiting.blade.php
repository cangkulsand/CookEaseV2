<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Generating Recipes…') }}
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto px-6 py-20 text-center"
         x-data="{
            status: 'pending',
            message: '',
            interval: null,
            poll() {
                fetch(@js(route('generate.status', ['generationId' => $generationId])), {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                })
                .then(r => r.ok ? r.json() : Promise.reject(r.status))
                .then(data => {
                    this.status = data.status;
                    if (data.status === 'complete' && data.redirect) {
                        clearInterval(this.interval);
                        window.location.href = data.redirect;
                    } else if (data.status === 'failed') {
                        clearInterval(this.interval);
                        this.message = data.message || 'Recipe generation failed.';
                    }
                })
                .catch(() => { /* transient error, keep polling */ });
            }
         }"
         x-init="
            poll();
            interval = setInterval(() => poll(), 2500);
         ">

        <template x-if="status !== 'failed'">
            <div>
                <div class="mx-auto mb-8 w-20 h-20 rounded-full border-4 border-primary-200 border-t-primary-500 animate-spin"></div>
                <h3 class="text-2xl font-semibold text-gray-800 mb-3">Cooking up your recipes…</h3>
                <p class="text-gray-600">
                    Our AI is putting together 12 personalised Malaysian recipes based on your ingredients.
                    This usually takes 10–20 seconds — please keep this tab open.
                </p>
            </div>
        </template>

        <template x-if="status === 'failed'">
            <div class="bg-danger-50 border border-danger-200 rounded-lg p-6">
                <h3 class="text-xl font-semibold text-danger-700 mb-2">Generation failed</h3>
                <p class="text-danger-700 mb-4" x-text="message"></p>
                <a href="{{ route('generate') }}"
                   class="inline-block bg-primary-500 text-white px-5 py-2 rounded hover:bg-primary-600 transition">
                    Try again
                </a>
            </div>
        </template>
    </div>
</x-app-layout>
