<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;

    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.key');
        $this->baseUrl = (string) config('services.gemini.url');
    }

    /**
     * Analyse l'évolution clinique d'un patient via Gemini.
     */
    public function analyzePatientEvolution(string $clinicalContext): ?string
    {
        return $this->generateWithSystemPrompt(
            config('gemini.patient_evolution_system_prompt'),
            $clinicalContext,
        );
    }

    /**
     * Aide au diagnostic pour une consultation en cours via Gemini.
     */
    public function analyzeConsultationDiagnosis(string $clinicalContext): ?string
    {
        return $this->generateWithSystemPrompt(
            config('gemini.consultation_diagnosis_system_prompt'),
            $clinicalContext,
        );
    }

    /**
     * Analyse un texte et retourne une conclusion.
     */
    public function analyserTexte(string $texteAAnalyser): ?string
    {
        $prompt = "Analyse le texte suivant et donnes-en une conclusion claire, concise et structurée :\n\n" . $texteAAnalyser;

        return $this->generateWithSystemPrompt(null, $prompt);
    }

    /**
     * Appel générique à l'API Gemini avec instruction système optionnelle.
     */
    public function generateWithSystemPrompt(?string $systemPrompt, string $userContent): ?string
    {
        if (blank($this->apiKey)) {
            Log::error('Clé API Gemini non configurée (GEMINI_API_KEY).');

            return null;
        }

        $model = 'gemini-2.5-flash:generateContent';
        $url = $this->baseUrl . $model . '?key=' . $this->apiKey;

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userContent],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'topP' => 0.9,
            ],
        ];

        if (filled($systemPrompt)) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ];
        }

        try {
            $response = Http::timeout(90)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if ($response->successful()) {
                $result = $response->json();

                return $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            Log::error('Erreur API Gemini : ' . $response->body());

            return null;
        } catch (\Exception $e) {
            Log::error('Exception lors de l\'appel à Gemini : ' . $e->getMessage());

            return null;
        }
    }
}
