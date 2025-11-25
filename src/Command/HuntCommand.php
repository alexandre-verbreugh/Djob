<?php

namespace App\Command;

use App\Service\JobHunter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:hunt', description: 'Scanne les jobs (France Travail + RSS)')]
class HuntCommand extends Command
{
    public function __construct(private JobHunter $hunter)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🕵️  Chasse aux jobs lancée...');

        // Le Hunter renvoie maintenant des Objets Job, plus des tableaux
        $jobs = $this->hunter->hunt(5);

        if (empty($jobs)) {
            $io->warning("Aucune NOUVELLE offre trouvée (elles sont peut-être déjà en base).");
            return Command::SUCCESS;
        }

        foreach ($jobs as $job) {
            // ON UTILISE LES GETTERS (->) AU LIEU DES CROCHETS ([])
            $score = $job->getScore();
            $title = $job->getTitle();
            $summary = $job->getSummary();
            
            $color = $score > 70 ? 'green' : ($score > 40 ? 'yellow' : 'red');
            
            $io->section($title);
            $io->writeln("<fg=$color>Score : $score/100</>");
            $io->text($summary ?? 'Pas de résumé');
            $io->newLine();
        }

        $io->success(count($jobs) . " nouvelles offres ajoutées en base ! 🚀");

        return Command::SUCCESS;
    }
}