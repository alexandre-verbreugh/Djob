<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class JobAnalyzer
{
    public function __construct(
        private HttpClientInterface $client,
        // On récupère la clé depuis le .env automatiquement
        #[Autowire('%env(DEEPSEEK_API_KEY)%')] 
        private string $apiKey
    ) {}

    public function analyze(string $rawDescription): ?array
    {
        // 1. Petit nettoyage pour économiser des tokens (et des sous)
        $cleanText = strip_tags($rawDescription);
        
        // On coupe si c'est trop long (DeepSeek a une limite, et ça ne sert à rien de lire 10km de texte)
        $cleanText = substr($cleanText, 0, 3000);

        // 2. Appel API DeepSeek
        try {
            $response = $this->client->request('POST', 'https://api.deepseek.com/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        [
                            'role' => 'system', 
                            'content' => 'Tu es un recruteur Tech expert. Analyse cette offre. Réponds UNIQUEMENT un JSON valide : {"score": int (0-100), "summary": string (1 phrase), "letter": string (brouillon motivé si score > 70, sinon null)}'
                        ],
                        [
                            'role' => 'user', 
                            'content' => 'Analyse cette offre : ' . $cleanText
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'] // Force le JSON, très important
                ],
            ]);

            // 3. On décode la réponse
            $content = $response->toArray();
            $jsonString = $content['choices'][0]['message']['content'] ?? '{}';
            
            return json_decode($jsonString, true);

        } catch (\Exception $e) {
            // Si ça plante (réseau, api down), on retourne null sans faire crasher l'app
            return null;
        }
    }

    // Ajoute cette nouvelle méthode dans ta classe JobAnalyzer
    // (Tu peux garder l'ancienne méthode 'analyze' pour le scoring si tu veux, 
    // mais voici celle pour la lettre spécifique)

    public function generateCoverLetter(string $jobTitle, string $jobDesc, string $myProfile): ?string
    {
        // On nettoie un peu
        $cleanDesc = substr(strip_tags($jobDesc), 0, 2000);

        try {
            $response = $this->client->request('POST', 'https://api.deepseek.com/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        [
                            'role' => 'system', 
                            'content' => "Tu es un expert en recrutement. Rédige une lettre de motivation pour le poste '$jobTitle'. 
                            Utilise les informations du candidat ci-dessous. 
                            Le ton doit être : " . $myProfile . "
                            Ne mets pas de placeholders [Nom] etc, signe 'Alexandre Verbreugh'."
                        ],
                        [
                            'role' => 'user', 
                            'content' => "Détails de l'offre : " . $cleanDesc
                        ],
                    ],
                ],
            ]);

            $content = $response->toArray()['choices'][0]['message']['content'] ?? null;
            return $content;

        } catch (\Exception $e) {
            return null;
        }
    }
}