<?php

namespace App\Controller;

use App\Form\SearchJobType;
use App\Entity\Job;
use App\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\FranceTravailService;
use App\Service\JobAnalyzer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HuntController extends AbstractController
{
    #[Route('/', name: 'app_hunt')]
    public function index(Request $request, FranceTravailService $ftService): Response
    {
        // 1. Création du formulaire
        // On passe des valeurs par défaut (Développeur, 18)
        $defaultData = ['keyword' => 'Informatique', 'location' => '18'];
        $form = $this->createForm(SearchJobType::class, $defaultData);

        $form->handleRequest($request);

        // 2. Récupération des données
        $jobs = [];
        $data = $form->getData(); // Contient soit les défauts, soit la recherche utilisateur

        if ($form->isSubmitted() && $form->isValid()) {
            // Si l'utilisateur a cherché quelque chose
            $data = $form->getData();
        }

        // 3. Appel API (avec les données du formulaire ou les défauts)
        // Petit check : si les champs sont vides, on met des valeurs par défaut pour pas que l'API plante
        $keyword = $data['keyword'] ?? 'Développeur';
        $location = $data['location'] ?? '18';

        $jobs = $ftService->searchJobs($keyword, $location);

        return $this->render('hunt/index.html.twig', [
            'form' => $form->createView(), // On envoie le formulaire à la vue
            'jobs' => $jobs,
        ]);
    }

    #[Route('/offre/{id}', name: 'app_job_show')]
    public function show(
        string $id, 
        FranceTravailService $ftService, 
        JobRepository $jobRepository
    ): Response
    {
        // 1. On récupère les détails frais depuis l'API
        $job = $ftService->getJobDetails($id);

        if (!$job) {
            $this->addFlash('error', 'Offre introuvable ou expirée.');
            return $this->redirectToRoute('app_hunt');
        }

        // 2. ON VÉRIFIE SI ON A DÉJÀ CETTE OFFRE EN BASE (Pour récupérer la lettre)
        $letter = null;
        
        // On reconstruit le lien comme on l'a fait pour la sauvegarde
        // (C'est notre clé unique pour retrouver l'offre)
        $link = $job['origineOffre']['urlOrigine'] 
             ?? $job['contact']['urlPostulation'] 
             ?? 'https://candidat.francetravail.fr/offres/recherche/detail/' . $id;

        $existingJob = $jobRepository->findOneBy(['link' => $link]);

        if ($existingJob) {
            // Si on la trouve en base, on récupère la lettre !
            $letter = $existingJob->getLetter();
        }

        return $this->render('hunt/show.html.twig', [
            'job' => $job,
            'generatedLetter' => $letter, // On passe la lettre à la vue (qu'elle vienne de la BDD ou soit null)
        ]);
    }

    #[Route('/offre/{id}/generate', name: 'app_job_generate_ai')]
    public function generateAi(
        string $id, 
        FranceTravailService $ftService,
        JobAnalyzer $analyzer,
        EntityManagerInterface $em,
        JobRepository $jobRepository,
        #[Autowire('%app.candidate_profile%')] string $myProfile
    ): Response
    {
        // 1. On récupère l'offre fraîche depuis l'API
        $apiJob = $ftService->getJobDetails($id);

        if (!$apiJob) {
            $this->addFlash('error', 'Offre introuvable.');
            return $this->redirectToRoute('app_hunt');
        }

        // 2. On génère la lettre (IA)
        $letter = $analyzer->generateCoverLetter(
            $apiJob['intitule'], 
            $apiJob['description'], 
            $myProfile
        );

        if ($letter) {
            // 3. SAUVEGARDE EN BDD 💾
            
            // On reconstruit le lien proprement (comme dans le Twig)
            $link = $apiJob['origineOffre']['urlOrigine'] 
                 ?? $apiJob['contact']['urlPostulation'] 
                 ?? 'https://candidat.francetravail.fr/offres/recherche/detail/' . $id;

            // On vérifie si l'offre existe déjà en base pour ne pas créer de doublon
            $existingJob = $jobRepository->findOneBy(['link' => $link]);

            if ($existingJob) {
                $job = $existingJob; // On met à jour l'existante
            } else {
                $job = new Job();    // On crée une nouvelle
                $job->setCreatedAt(new \DateTimeImmutable());
                $job->setLink($link);
                $job->setTitle($apiJob['intitule']);
                $job->setSummary($apiJob['description']); // On stocke toute la desc dans le summary
                $job->setScore(100); // On met 100 arbitrairement car TU as choisi cette offre
            }

            // On ajoute la lettre générée
            $job->setLetter($letter);

            // On persiste
            $em->persist($job);
            $em->flush();

            $this->addFlash('success', 'Offre et lettre sauvegardées en base ! 🎉');
        }

        // 4. On ré-affiche la vue
        return $this->render('hunt/show.html.twig', [
            'job' => $apiJob,
            'generatedLetter' => $letter
        ]);
    }

    #[Route('/mes-candidatures', name: 'app_my_jobs')]
    public function myJobs(JobRepository $jobRepository): Response
    {
        // On récupère tout ce qui est en base (donc ce que tu as sauvegardé)
        $jobs = $jobRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('hunt/my_jobs.html.twig', [
            'jobs' => $jobs,
        ]);
    }
    #[Route('/job/{id}/toggle-applied', name: 'app_job_toggle_applied')]
    public function toggleApplied(Job $job, EntityManagerInterface $em, Request $request): Response
    {
        $job->setApplied(!$job->isApplied());
        $em->flush();

        $this->addFlash('success', 'Statut mis à jour !');

        return $this->redirect($request->headers->get('referer'));
    }
}