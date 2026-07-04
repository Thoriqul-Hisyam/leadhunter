<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GroqApiService
{
    protected $apiKey;
    protected $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = env('GROQ_API_KEY');
    }

    public function generateText(string $prompt)
    {
        // If no API key is set, return a dummy message for MVP testing
        if (!$this->apiKey) {
            return "Hello,\n\nI noticed your business online and really liked what you're doing. I was wondering if you'd be open to a quick chat about improving your online presence?\n\nBest,\nLeadHunter";
        }

        try {
            $response = Http::withToken($this->apiKey)->post($this->apiUrl, [
                'model' => 'llama-3.1-8b-instant',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert B2B sales consultant and copywriter. Keep communications formal, professional, highly persuasive, and focused on business value proposition.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.7,
                'max_tokens' => 1024,
            ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            } else {
                throw new \Exception("Groq API Error: " . $response->body());
            }
        } catch (\Exception $e) {
            throw new \Exception("Could not generate message. Please check API key. Details: " . $e->getMessage());
        }
    }
}
