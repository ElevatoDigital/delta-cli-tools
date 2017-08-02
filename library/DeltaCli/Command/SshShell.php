<?php

namespace DeltaCli\Command;

use DeltaCli\Command;
use DeltaCli\Debug;
use DeltaCli\Environment;
use DeltaCli\Host;
use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Input\ArrayInput;
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

        $script = $this->generateScript($env, $host);
        $script->run(new ArrayInput([]), $output);

        $tunnel = $host->getSshTunnel();
        $tunnel->setUp();

        $command = $tunnel->assembleSshCommand(null, '-t');
        Debug::log("Opening SSH shell with `{$command}`...");
        deltacli_wrap_command($command);
        passthru($command);

        $tunnel->tearDown();
    }

    private function generateScript(Environment $env, Host $host)
    {
        $script = new Script(
            $this->project,
            'open-ssh-shell',
            'Script that runs prior to opening an SSH shell and sends notifications.'
        );

        $script->setApplication($this->getApplication());

        $script
            ->setEnvironment($env)
            ->addStep($this->project->logAndSendNotifications()->setSendNotificationsOnScriptFailure(false))
            ->addStep($this->project->fixSshKeyPermissions())
            ->addStep(
                'open-ssh-shell',
                function () use ($host, $env) {
                    echo "Opening SSH shell to '{$host->getHostname()}' on {$env->getName()}." . PHP_EOL;
                }
            );

        return $script;
    }
}
