<?php

namespace DeltaCli;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputOption;

class Command extends SymfonyCommand
{
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->addOption(
            'tunnel-via',
            null,
            InputOption::VALUE_REQUIRED,
            'An environment via which SSH should be tunneled.'
        );
    }
}
