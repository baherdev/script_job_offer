<?php

namespace App\Command;

use App\Repository\SearchQueryRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:scrape-active-queries',
    description: 'Lance le scraping LinkedIn pour toutes les recherches actives',
)]
class AppScrapeActiveQueriesCommand extends Command
{
    public function __construct(private SearchQueryRepository $repo)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $queries = $this->repo->findBy(['isActive' => true]);

        if (empty($queries)) {
            $io->warning('Aucune recherche active trouvée.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('%d recherche(s) active(s) trouvée(s).', count($queries)));

        foreach ($queries as $query) {
            $keyword  = escapeshellarg($query->getKeyword());
            $location = $query->getLocation() ? '--location ' . escapeshellarg($query->getLocation()) : '';
            $distance = $query->getDistance() ? '--distance ' . (int)$query->getDistance() : '';

            $cmd = "python /app/linkedin_job_search.py {$keyword} {$location} {$distance} --backend http://nginx/api/job-offers";

            $io->text("Lancement : {$cmd}");
            shell_exec($cmd);
        }

        $io->success('Scraping terminé.');
        return Command::SUCCESS;
    }
}
