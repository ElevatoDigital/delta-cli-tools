<?php

namespace DeltaCli\Exception;

use Exception;
use Symfony\Component\Console\Output\OutputInterface;

class UnrecognizedStepInput extends Exception implements ConsoleOutputInterface
{
    public function outputToConsole(OutputInterface $output)
    {
        $output->writeln(
            [
                'We could not interpret the input you passed to addStep().  These are the types of steps accepted:',
                '',
                'PHP Function:',
                "  \$script->addStep('step-name', function () {});",
                'Shell Command:',
                "  \$script->addStep('ls /my/dir');",
                'Step Object:',
                "  \$script->addStep(new StepClass());"
            ]
        );
    }
}
