<?php

namespace DeltaCli\Exception;

use DeltaCli\Console\Output\Banner;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

class NoOtherStepsCanBeAddedAfterWatch extends Exception implements ConsoleOutputInterface
{
    public function hasBanner()
    {
        return true;
    }

    public function outputToConsole(OutputInterface $output)
    {
        $banner = new Banner($output);
        $banner
            ->setBackground('red')
            ->render('Only additional watch() steps can be added after a watch step.');

        $output->writeln(
            [
                'Because watch() steps block further script execution, no step types other than',
                'additional watch() steps can be added after your first watch() step.'
            ]
        );
    }
}
