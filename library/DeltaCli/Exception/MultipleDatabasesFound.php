<?php

namespace DeltaCli\Exception;

use DeltaCli\Console\Output\Banner;
use Symfony\Component\Console\Helper\Table;
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
