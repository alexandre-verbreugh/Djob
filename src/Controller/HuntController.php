<?php

namespace App\Controller;

use App\Entity\Job;
use App\Repository\JobRepository;
use App\Service\JobAnalyzer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class HuntController extends AbstractController
{
    #[Route('/', name: 'app_hunt')]
    public function index(JobRepository $jobRepository): Response
    {
        // On affiche tout, trié par date (les plus récents en haut)
        $jobs = $jobRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('hunt/index.html.twig', [
            'jobs' => $jobs,
        ]);
    }

    // --- NOUVELLE ROUTE POUR LE BOUTON ---
    #[Route('/job/{id}/generate-letter', name: 'app_job_generate_letter')]
    public function generateLetter(
        Job $job, 
        JobAnalyzer $analyzer, 
        EntityManagerInterface $em,
        #[Autowire('%app.candidate_profile%')] string $myProfile // On récupère ton profil
    ): Response
    {
        // On appelle DeepSeek avec ton profil + l'offre
        $analysis = $analyzer->generateCoverLetter($job->getTitle(), $job->getSummary(), $myProfile);

        if ($analysis) {
            $job->setLetter($analysis);
            $em->flush(); // On sauvegarde en base
            $this->addFlash('success', 'Lettre générée avec succès ! 🚀');
        } else {
            $this->addFlash('error', 'Erreur lors de la génération (API HS ?)');
        }

        // On revient sur la liste (le navigateur descendra automatiquement à la bonne hauteur si on gère les ancres, mais restons simples)
        return $this->redirectToRoute('app_hunt');
    }
}