<?php

namespace DeltaCli;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

        $this->addOption(
            'vpn',
            null,
            InputOption::VALUE_NONE,
            'Tunnel all SSH connections over the Delta VPN.'
        );

        $this->addOption(
            'authorization-code',
            null,
            InputOption::VALUE_REQUIRED,
            'Authorization code for potentially destructive operations.'
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if ($input instanceof ArgvInput) {
            $input->setCommand($this);
        }
    }
}
