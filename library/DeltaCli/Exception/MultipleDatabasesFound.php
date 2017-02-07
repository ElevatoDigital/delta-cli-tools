<?php

namespace DeltaCli\Exception;

use Symfony\Component\Console\Output\OutputInterface;

class MultipleDatabasesFound extends AbstractDatabaseSelectionError
{
    public function getBannerTitle()
    {
        return 'Multiple databases found.';
    }

    public function displayMessage(OutputInterface $output)
    {
        $output->writeln(
            [
                "The {$this->environment->getName()} environment has multiple databases available.  Use the following",
                'options with your command to select which database you want to use:',
                '',

            ]
        );
    }
}
