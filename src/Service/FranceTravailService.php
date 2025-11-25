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

        $response = $this->client->request('POST', 'https://entreprise.francetravail.fr/connexion/oauth2/access_token', [
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
                    'commune' => $location,
                    'range' => '0-9', 
                    'sort' => '1',
                ]
            ]);

            if ($response->getStatusCode() === 204) {
                return [];
            }

            return $response->toArray()['resultats'] ?? [];

        } catch (\Exception $e) {
            return [];
        }
    }
}