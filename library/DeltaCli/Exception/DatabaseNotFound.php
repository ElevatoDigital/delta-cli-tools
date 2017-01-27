<?php

namespace DeltaCli\Exception;

use Symfony\Component\Console\Output\OutputInterface;

class DatabaseNotFound extends AbstractDatabaseSelectionError
{
    public function getBannerTitle()
    {
        return 'No matching database found.';
    }

    public function displayMessage(OutputInterface $output)
    {
        $output->writeln(
            [
                "The {$this->environment->getName()} environment has no database matching your specified criteria.",
                ''
            ]
        );
    }

}
