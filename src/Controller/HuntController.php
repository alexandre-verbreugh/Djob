<?php

namespace App\Controller;

use App\Repository\JobRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

class HuntController extends AbstractController
{
    #[Route('/', name: 'app_hunt')]
    public function index(JobRepository $jobRepository, Request $request): Response
    {
        // Petit bonus : On ajoute un filtre simple sur le score min
        // Si tu tapes /?min=80 dans l'URL, ça filtre.
        $minScore = $request->query->getInt('min', 0);

        // On récupère tout l'historique trié par les meilleures notes
        $jobs = $jobRepository->createQueryBuilder('j')
            ->where('j.score >= :min')
            ->setParameter('min', $minScore)
            ->orderBy('j.score', 'DESC') // Les cracks en premier
            ->addOrderBy('j.createdAt', 'DESC') // Puis les plus récents
            ->getQuery()
            ->getResult();

        return $this->render('hunt/index.html.twig', [
            'jobs' => $jobs,
        ]);
    }
}
