<?php

namespace DeltaCli\Exception;

use Exception;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectNotConfigured extends Exception implements ConsoleOutputInterface
{
    public function hasBanner()
    {
        return false;
    }
    
    public function outputToConsole(OutputInterface $output)
    {
        $output->writeln(
            [
                'Your project has not been configured.  Add a <fg=white;options=bold>delta-cli.php</> file',
                'to the root folder of your project.  In that file you will write your deploy',
                'script and define the environmental dependencies for your project.  There are',
                'easy to use templates for common Delta project types.',
                '',
                'Learn more here:',
                '  <fg=blue;options=underscore>https://github.com/DeltaSystems/delta-cli-tools</>',
                '',
                'Get started using the default delta-cli.php template by running:',
                '  delta create-project-config'
            ]
        );
    }
}
