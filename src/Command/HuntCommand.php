<?php

namespace App\Command;

use App\Service\JobAnalyzer;
use App\Service\JobHunter;
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
        private JobHunter $hunter
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('🕵️  Chasse aux jobs lancée...');

        $results = $this->hunter->hunt(3);

        if (empty($results)) {
            $io->warning("Aucun résultat trouvé ou erreur lors de la lecture du flux.");
            return Command::FAILURE;
        }

        foreach ($results as $result) {
            $io->section("Analyse de : " . $result['title']);
            
            $score = $result['score'];
            $color = $score > 70 ? 'green' : ($score > 40 ? 'yellow' : 'red');
            
            $io->writeln("<fg=$color>Score : $score/100</>");
            $io->text("Résumé : " . $result['summary']);

            if ($score > 70) {
                $io->success("🔥 CIBLE DÉTECTÉE !");
                $io->note("Brouillon de lettre : \n" . ($result['letter'] ?? ''));
            } else {
                $io->text("Pas intéressant.");
            }
        }

        return Command::SUCCESS;
    }
}