<?php

namespace App\Command;

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
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cmd = 'python /app/linkedin_job_search.py --backend http://nginx/api/job-offers';

        $io->text("Lancement : {$cmd}");
        shell_exec($cmd);

        $io->success('Scraping terminé.');
        return Command::SUCCESS;
    }
}
