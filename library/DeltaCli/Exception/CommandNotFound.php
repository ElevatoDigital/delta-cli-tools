<?php

namespace DeltaCli\Exception;

use DeltaCli\Console\Output\Banner;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

class CommandNotFound extends Exception implements ConsoleOutputInterface
{
    private $command;

    public function setCommand($command)
    {
        $this->command = $command;

        return $this;
    }

    public function hasBanner()
    {
        return true;
    }

    public function outputToConsole(OutputInterface $output)
    {
        $banner = new Banner($output);
        $banner
            ->setBackground('red')
            ->render("{$this->command} is not installed.");

        $output->writeln(
            [
                "This script requires {$this->command} but it could not be found on your system.",
                'Please install it and try again.'
            ]
        );
    }
}
