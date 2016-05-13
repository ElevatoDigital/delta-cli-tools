<?php

namespace DeltaCli\Exception;

use Symfony\Component\Console\Output\OutputInterface;

interface ConsoleOutputInterface
{
    public function outputToConsole(OutputInterface $output);

    /**
     * @return bool
     */
    public function hasBanner();
}
