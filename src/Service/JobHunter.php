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
        private JobRepository $jobRepository
    ) {}

    public function hunt(int $limit = 3): array
    {
        $rssUrl = 'https://weworkremotely.com/categories/remote-back-end-programming-jobs.rss';
        
        // On charge le RSS (avec gestion d'erreur basique)
        $rss = @simplexml_load_file($rssUrl);
        if ($rss === false) {
            return [];
        }

        $jobsToReturn = [];
        $count = 0;

        foreach ($rss->channel->item as $item) {
            if ($count >= $limit) {
                break;
            }

            $link = (string)$item->link;
            
            // --- C'EST ICI QUE ÇA CHANGE ---
            
            // 1. On regarde si l'offre est déjà en base (MySQL)
            $existingJob = $this->jobRepository->findOneBy(['link' => $link]);

            if ($existingJob) {
                // Si oui : On la récupère GRATUITEMENT (pas d'appel API)
                $jobsToReturn[] = $existingJob;
                $count++;
                continue; // On passe à la suivante
            }

            // 2. Si non : On appelle DeepSeek (PAYANT)
            $desc = (string)$item->description;
            $title = (string)$item->title;

            sleep(1); // Politesse API
            $analysis = $this->analyzer->analyze($desc);

            if ($analysis) {
                // 3. On sauvegarde le résultat pour la prochaine fois
                $newJob = new Job();
                $newJob->setTitle($title);
                $newJob->setLink($link);
                $newJob->setScore($analysis['score'] ?? 0);
                $newJob->setSummary($analysis['summary'] ?? 'Pas de résumé');
                $newJob->setLetter($analysis['letter'] ?? null);
                $newJob->setCreatedAt(new \DateTimeImmutable());

                $this->em->persist($newJob);
                $this->em->flush(); // Envoi vers MySQL

                $jobsToReturn[] = $newJob;
                $count++;
            }
        }

        return $jobsToReturn;
    }
}