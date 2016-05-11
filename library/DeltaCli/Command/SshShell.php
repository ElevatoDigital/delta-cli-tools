<?php

namespace DeltaCli\Command;

use DeltaCli\Debug;
use DeltaCli\Exception\MustSpecifyHostnameForShell;
use DeltaCli\Host;
use DeltaCli\Project;
use Symfony\Component\Console\Command\Command;
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
        $host = $this->getHostForEnvironment(
            $input->getArgument('environment'),
            $input->getOption('hostname')
        );

        $permissionsStep = $this->project->fixSshKeyPermissions();
        $permissionsStep->run();

        $command = $host->assembleSshCommand(null)->setAdditionalFlags('-t');
        Debug::log("Opening SSH shell with `{$command}`...");
        passthru($command);
    }

    private function getHostForEnvironment($environmentName, $hostname)
    {
        $selected    = null;
        $environment = $this->project->getEnvironment($environmentName);
        $hosts       = $environment->getHosts();

        if (1 === count($hosts)) {
            $selected = current($hosts);
        } else {
            if (!$hostname) {
                $hostCount = count($hosts);

                throw new MustSpecifyHostnameForShell(
                    "The {$environment->getName()} environment has {$hostCount} hosts, so you must"
                    . "specify which host you'd like to shell into with the hostname option."
                );
            }

            /* @var $host Host */
            foreach ($hosts as $host) {
                if ($host->getHostname() === $hostname) {
                    $selected = $host;
                    break;
                }
            }

            if (!$selected) {
                throw new MustSpecifyHostnameForShell(
                    "No host could be found with the hostname {$hostname}."
                );
            }
        }

        if (!$selected->hasRequirementsForSshUse()) {
            throw new MustSpecifyHostnameForShell(
                "The {$selected->getHostname()} host is not configured for SSH which needs a username and hostname."
            );
        }

        return $selected;
    }
}
