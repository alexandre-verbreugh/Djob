<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class FranceTravailService
{
    private ?string $accessToken = null;

    public function __construct(
        private HttpClientInterface $client,
        #[Autowire('%env(FRANCE_TRAVAIL_CLIENT_ID)%')] private string $clientId,
        #[Autowire('%env(FRANCE_TRAVAIL_SECRET)%')] private string $clientSecret
    ) {}

    private function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        // CORRECTION : On met le realm directement dans l'URL !
        $url = 'https://entreprise.francetravail.fr/connexion/oauth2/access_token?realm=%2Fpartenaire';

        $response = $this->client->request('POST', $url, [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => 'api_offresdemploiv2 o2dsoffre',
            ],
        ]);

        $data = $response->toArray();
        $this->accessToken = $data['access_token'];

        return $this->accessToken;
    }

    public function searchJobs(string $keyword = 'Symfony', string $location = '75'): array
    {
        try {
            $token = $this->getAccessToken();

            $response = $this->client->request('GET', 'https://api.francetravail.io/partenaire/offresdemploi/v2/offres/search', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'motsCles' => $keyword,
                    // CORRECTION ICI : On utilise 'departement' au lieu de 'commune' pour "75"
                    'departement' => $location, 
                    'range' => '0-9', 
                    'sort' => '1',
                ]
            ]);

            $results = $response->toArray()['resultats'] ?? [];

            // On ajoute la source à chaque offre
            return array_map(function($job) {
                $job['source'] = 'france_travail';
                return $job;
            }, $results);

        } catch (\Exception $e) {
            // En production, l'idéal serait de loguer l'erreur ici (ex: $this->logger->error($e))
            // Mais pour l'instant, on retourne silencieusement un tableau vide pour ne pas casser le script
            return [];
        }
    }

    // Récupère une offre unique par son ID
    public function getJobDetails(string $id): ?array
    {
        try {
            $token = $this->getAccessToken();

            $response = $this->client->request('GET', 'https://api.francetravail.io/partenaire/offresdemploi/v2/offres/' . $id, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                return $response->toArray();
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}