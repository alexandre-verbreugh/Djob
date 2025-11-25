<?php

namespace App\Command;

use App\Service\JobAnalyzer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:hunt',
    description: 'Scanne le web à la recherche de jobs et analyse avec IA',
)]
class HuntCommand extends Command
{
    public function __construct(
        private JobAnalyzer $analyzer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rssUrl = 'https://weworkremotely.com/categories/remote-back-end-programming-jobs.rss';
        
        $io->title('🕵️  Chasse aux jobs lancée...');

        // 1. Récupération du flux RSS
        $io->text("Lecture du flux RSS : $rssUrl");
        
        // simplexml est natif en PHP, c'est le plus simple pour du RSS
        $rss = simplexml_load_file($rssUrl);

        if ($rss === false) {
            $io->error("Impossible de lire le flux RSS.");
            return Command::FAILURE;
        }

        $count = 0;
        $maxTests = 3; // SÉCURITÉ : On ne teste que 3 offres pour commencer

        foreach ($rss->channel->item as $item) {
            // Petite sécurité pour ne pas tout scanner d'un coup
            if ($count >= $maxTests) {
                $io->warning("Limite de test atteinte ($maxTests offres). Arrêt pour économiser l'API.");
                break;
            }

            $title = (string)$item->title;
            $link = (string)$item->link;
            $desc = (string)$item->description; // Contient le HTML de l'offre

            $io->section("Analyse de : $title");
            
            // Appel au service IA
            $io->text("🧠 Interrogation de DeepSeek...");
            $result = $this->analyzer->analyze($desc);

            if (!$result) {
                $io->error("Erreur lors de l'analyse API.");
                continue;
            }

            // Affichage du résultat
            $score = $result['score'] ?? 0;
            $color = $score > 70 ? 'green' : ($score > 40 ? 'yellow' : 'red');
            
            $io->writeln("<fg=$color>Score : $score/100</>");
            $io->text("Résumé : " . ($result['summary'] ?? 'Pas de résumé'));

            if ($score > 70) {
                $io->success("🔥 CIBLE DÉTECTÉE !");
                $io->note("Brouillon de lettre : \n" . ($result['letter'] ?? ''));
                // TODO: Ici on ajoutera l'envoi de mail plus tard
            } else {
                $io->text("Pas intéressant.");
            }

            $count++;
            sleep(1); // Petite pause pour être poli avec l'API
        }

        return Command::SUCCESS;
    }
}