<?php

namespace App\Service;

use App\Entity\Job;
use App\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;

class JobHunter
{
    public function __construct(
        private JobAnalyzer $analyzer,
        private EntityManagerInterface $em,
        private JobRepository $jobRepository,
        private FranceTravailService $ftService // <-- On injecte le nouveau service ici
    ) {}

    public function hunt(int $limit = 5): array
    {
        $jobsToReturn = [];
        $totalCount = 0;

        // --- 1. SOURCE : FRANCE TRAVAIL ---
        // On cherche "Symfony" sur "75" (Paris). Tu pourras changer ça plus tard.
        $ftJobs = $this->ftService->searchJobs('Symfony', '75');
        
        foreach ($ftJobs as $ftJob) {
            if ($totalCount >= $limit) break;

            // On construit le lien vers l'offre
            $link = $ftJob['origineOffre']['urlOrigine'] ?? 'https://candidat.pole-emploi.fr/offres/recherche/detail/' . $ftJob['id'];
            
            // Si on l'a déjà en base, on passe
            if ($this->jobRepository->findOneBy(['link' => $link])) {
                continue; 
            }

            // On prépare les données pour l'IA
            $title = $ftJob['intitule'];
            $description = $ftJob['description'];

            // On analyse et sauvegarde
            $this->processAndSave($title, $link, $description, $jobsToReturn);
            $totalCount++;
        }

        // --- 2. SOURCE : FLUX RSS (Si on n'a pas atteint la limite) ---
        if ($totalCount < $limit) {
            $rssUrl = 'https://weworkremotely.com/categories/remote-back-end-programming-jobs.rss';
            $rss = @simplexml_load_file($rssUrl);

            if ($rss) {
                foreach ($rss->channel->item as $item) {
                    if ($totalCount >= $limit) break;

                    $link = (string)$item->link;
                    if ($this->jobRepository->findOneBy(['link' => $link])) {
                        continue;
                    }

                    $this->processAndSave((string)$item->title, $link, (string)$item->description, $jobsToReturn);
                    $totalCount++;
                }
            }
        }

        return $jobsToReturn;
    }

    // Fonction d'aide pour analyser et sauvegarder (évite de copier-coller le code 2 fois)
    private function processAndSave(string $title, string $link, string $description, array &$results): void
    {
        // Petite pause pour ne pas brusquer l'API DeepSeek
        sleep(1); 

        $analysis = $this->analyzer->analyze($description);

        if ($analysis) {
            $newJob = new Job();
            $newJob->setTitle($title);
            $newJob->setLink($link);
            $newJob->setScore($analysis['score'] ?? 0);
            $newJob->setSummary($analysis['summary'] ?? 'Pas de résumé');
            $newJob->setLetter($analysis['letter'] ?? null);
            $newJob->setCreatedAt(new \DateTimeImmutable());

            $this->em->persist($newJob);
            $this->em->flush();

            $results[] = $newJob;
        }
    }
}