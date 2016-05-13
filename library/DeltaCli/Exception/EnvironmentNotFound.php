<?php

namespace DeltaCli\Exception;

use Exception;
use Symfony\Component\Console\Output\OutputInterface;

class EnvironmentNotFound extends Exception implements ConsoleOutputInterface
{
    private $name;

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function hasBanner()
    {
        return false;
    }

    public function outputToConsole(OutputInterface $output)
    {
        $output->writeln(
            [
                "We could not find an environment with the name '{$this->name}'.  You can add environments",
                'in your delta-cli.php file using $project->createEnvironment().'
            ]
        );
    }
}
