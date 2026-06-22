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
     * @return array{text: ?string, user_error: ?string}
     */
    public function analyzePatientEvolution(string $clinicalContext): array
    {
        return $this->generateWithSystemPrompt(
            config('gemini.patient_evolution_system_prompt'),
            $clinicalContext,
        );
    }

    /**
     * @return array{text: ?string, user_error: ?string}
     */
    public function analyzeConsultationDiagnosis(string $clinicalContext): array
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

        return $this->generateWithSystemPrompt(null, $prompt)['text'];
    }

    /**
     * Appel générique à l'API Gemini avec instruction système optionnelle.
     *
     * @return array{text: ?string, user_error: ?string}
     */
    public function generateWithSystemPrompt(?string $systemPrompt, string $userContent): array
    {
        if (blank($this->apiKey)) {
            Log::error('Clé API Gemini non configurée (GEMINI_API_KEY).');

            return [
                'text' => null,
                'user_error' => 'La clé API Gemini n\'est pas configurée (GEMINI_API_KEY).',
            ];
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
                $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if (blank($text)) {
                    return [
                        'text' => null,
                        'user_error' => 'L\'analyse n\'a pas pu être générée. Réessayez dans quelques instants.',
                    ];
                }

                return ['text' => $text, 'user_error' => null];
            }

            Log::error('Erreur API Gemini : ' . $response->body());

            return [
                'text' => null,
                'user_error' => $this->userErrorForStatus($response->status()),
            ];
        } catch (\Exception $e) {
            Log::error('Exception lors de l\'appel à Gemini : ' . $e->getMessage());

            return [
                'text' => null,
                'user_error' => 'Impossible de joindre le service d\'analyse. Réessayez dans quelques instants.',
            ];
        }
    }

    protected function userErrorForStatus(int $status): string
    {
        return match (true) {
            in_array($status, [429, 503], true) => 'Le service d\'analyse est temporairement surchargé. Réessayez dans quelques instants.',
            $status === 401, $status === 403 => 'La clé API Gemini est invalide ou n\'a pas les permissions nécessaires.',
            default => 'L\'analyse n\'a pas pu être générée. Vérifiez la configuration de l\'API Gemini ou réessayez.',
        };
    }
}
