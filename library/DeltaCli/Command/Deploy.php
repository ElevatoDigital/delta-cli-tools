<?php

namespace DeltaCli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Deploy extends Command
{
    protected function configure()
    {
        $this
            ->setName('deploy')
            ->setDescription('Deploy this project.')
            ->addArgument(
                'environment',
                InputArgument::REQUIRED
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Hey there!');
    }
}
