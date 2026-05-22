<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateRecipesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 90;

    public function __construct(
        public string $generationId,
        public int $userId,
        public string $prompt,
        public string $ingredientsText,
    ) {}

    public static function cacheKey(int $userId, string $generationId): string
    {
        return "generation:{$userId}:{$generationId}";
    }

    public function handle(): void
    {
        $apiKey = config('services.groq.key');

        if (! $apiKey) {
            $this->writeFailure('AI service is currently unavailable. Please contact the admin.');

            return;
        }

        $response = Http::timeout(60)->withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => 'meta-llama/llama-4-scout-17b-16e-instruct',
            'messages' => [
                ['role' => 'user', 'content' => $this->prompt],
            ],
        ]);

        $json = $response->json();

        if (isset($json['error'])) {
            Log::error('Groq API error: '.$json['error']['message']);
            $this->writeFailure('Failed to generate recipe: '.$json['error']['message']);

            return;
        }

        $aiContent = trim($json['choices'][0]['message']['content'] ?? '');

        // Remove markdown code block wrapper
        if (str_starts_with($aiContent, '```json')) {
            $aiContent = preg_replace('/^```json\s*/i', '', $aiContent);
            $aiContent = preg_replace('/```$/', '', $aiContent);
            $aiContent = trim($aiContent);
        }

        // Attempt normal decode first
        $recipes = json_decode($aiContent, true);

        // If invalid JSON, attempt recovery
        if (json_last_error() !== JSON_ERROR_NONE) {

            Log::warning('AI returned invalid JSON, attempting repair', [
                'error' => json_last_error_msg(),
            ]);

            // Find last complete object
            $lastBrace = strrpos($aiContent, '}');

            if ($lastBrace !== false) {

                // Keep valid portion only
                $repairedJson = substr($aiContent, 0, $lastBrace + 1);

                // Remove trailing comma
                $repairedJson = rtrim($repairedJson, ',');

                // Close array if missing
                if (! str_ends_with(trim($repairedJson), ']')) {
                    $repairedJson .= ']';
                }

                // Try decode again
                $recipes = json_decode($repairedJson, true);

                if (json_last_error() === JSON_ERROR_NONE) {

                    Log::info('Successfully repaired AI JSON response');

                } else {

                    Log::error('Failed to repair AI JSON', [
                        'error' => json_last_error_msg(),
                        'content' => $aiContent,
                    ]);

                    $this->writeFailure(
                        'AI returned invalid JSON: ' . json_last_error_msg()
                    );

                    return;
                }

            } else {

                Log::error('AI returned invalid JSON with no recoverable object', [
                    'error' => json_last_error_msg(),
                    'content' => $aiContent,
                ]);

                $this->writeFailure(
                    'AI returned invalid JSON: ' . json_last_error_msg()
                );

                return;
            }
        }

        // Ensure recipes is always an array
        if (! is_array($recipes)) {

            Log::error('AI returned JSON but not an array', [
                'content' => $aiContent,
            ]);

            $this->writeFailure('AI did not return usable recipe data.');

            return;
        }

        foreach ($recipes as &$recipe) {
            $recipe['image'] = $this->getImageFromPixabay($recipe['name'] ?? '');
        }
        unset($recipe);

        Cache::put(self::cacheKey($this->userId, $this->generationId), [
            'status' => 'complete',
            'recipes' => $recipes,
            'ingredients_used' => $this->ingredientsText,
        ], now()->addHour());
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateRecipesJob exception: '.$exception->getMessage());
        $this->writeFailure('An error occurred while contacting the AI service.');
    }

    protected function writeFailure(string $message): void
    {
        Cache::put(self::cacheKey($this->userId, $this->generationId), [
            'status' => 'failed',
            'message' => $message,
        ], now()->addHour());
    }

    protected function getImageFromPixabay(string $query): string
    {
        if ($query === '') {
            return asset('images/placeholder.jpg');
        }

        try {
            $response = Http::timeout(10)->get('https://pixabay.com/api/', [
                'key' => config('services.pixabay.key'),
                'q' => $query,
                'image_type' => 'photo',
                'category' => 'food',
                'safesearch' => true,
                'per_page' => 3,
            ]);

            if ($response->successful() && isset($response['hits'][0]['webformatURL'])) {
                return $response['hits'][0]['webformatURL'];
            }
        } catch (\Throwable $e) {
            Log::warning('Pixabay fetch failed for "'.$query.'": '.$e->getMessage());
        }

        return asset('images/placeholder.jpg');
    }
}
