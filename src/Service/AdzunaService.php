<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AdzunaService
{
    public function __construct(
        private HttpClientInterface $client,
        #[Autowire('%env(ADZUNA_APP_ID)%')] private string $appId,
        #[Autowire('%env(ADZUNA_APP_KEY)%')] private string $appKey
    ) {}

    public function searchJobs(string $keyword = 'Développeur', string $location = 'Bourges', int $page = 1): array
    {
        try {
            $response = $this->client->request('GET', 'https://api.adzuna.com/v1/api/jobs/fr/search/' . $page, [
                'query' => [
                    'app_id' => $this->appId,
                    'app_key' => $this->appKey,
                    'what' => $keyword,
                    'where' => $location,
                    'results_per_page' => 10,
                    'sort_by' => 'date',
                ]
            ]);

            $data = $response->toArray();
            
            // On normalise la structure pour qu'elle ressemble à France Travail
            return array_map(function($job) use ($location) {
                return [
                    'id' => $job['id'] ?? 'adzuna-' . uniqid(),
                    'intitule' => $job['title'] ?? 'Titre non spécifié',
                    'description' => $job['description'] ?? '',
                    'entreprise' => [
                        'nom' => $job['company']['display_name'] ?? 'Non spécifié'
                    ],
                    'lieuTravail' => [
                        'libelle' => $job['location']['display_name'] ?? $location
                    ],
                    'dateCreation' => $job['created'] ?? date('Y-m-d'),
                    'typeContrat' => $job['contract_type'] ?? null,
                    'typeContratLibelle' => $job['contract_time'] ?? 'Non spécifié',
                    'salaire' => [
                        'libelle' => (isset($job['salary_min']) && isset($job['salary_max'])) 
                            ? number_format($job['salary_min'], 0, ',', ' ') . '€ - ' . number_format($job['salary_max'], 0, ',', ' ') . '€'
                            : null
                    ],
                    'origineOffre' => [
                        'urlOrigine' => $job['redirect_url'] ?? '#'
                    ],
                    'source' => 'adzuna'
                ];
            }, $data['results'] ?? []);

        } catch (\Exception $e) {
            return [];
        }
    }

    public function getJobDetails(string $id): ?array
    {
        try {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}