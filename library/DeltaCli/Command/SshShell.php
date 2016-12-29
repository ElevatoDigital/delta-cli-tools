<?php

namespace DeltaCli\Command;

use DeltaCli\Command;
use DeltaCli\Debug;
use DeltaCli\Project;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SshShell extends Command
{
    /**
     * @var Project
     */
    private $project;

    public function __construct(Project $project)
    {
        parent::__construct(null);

        $this->project = $project;
    }

    protected function configure()
    {
        $this
            ->setName('ssh:shell')
            ->setDescription('Open a remote SSH shell.')
            ->addArgument('environment', InputArgument::REQUIRED, 'The environment where you want to open a shell.')
            ->addOption('hostname', null, InputOption::VALUE_REQUIRED, 'The specific hostname you want to connect to.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $env  = $this->project->getSelectedEnvironment();
        $host = $env->getSelectedHost($input->getOption('hostname'));

        $permissionsStep = $this->project->fixSshKeyPermissions();
        $permissionsStep->run();


        $tunnel = $host->getSshTunnel();
        $tunnel->setUp();

        $command = $tunnel->assembleSshCommand(null, '-t');
        Debug::log("Opening SSH shell with `{$command}`...");
        passthru($command);

        $tunnel->tearDown();
    }
}
