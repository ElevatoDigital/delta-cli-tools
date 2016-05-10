<?php

namespace DeltaCli\Command;

use DeltaCli\Environment;
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
            ->addOption('hostname', null, InputOption::VALUE_REQUIRED, 'The specific hostname you want to connect to.')
            ->addOption('tunnel-via', null, InputOption::VALUE_REQUIRED, 'An environment via which to tunnel.')
            ->addOption('tunnel-via-hostname', null, InputOption::VALUE_REQUIRED, 'The specific hostname to tunnel via.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $host = $this->getHostForEnvironment(
            $input->getArgument('environment'),
            $input->getOption('hostname')
        );

        $permissionsStep = $this->project->fixSshKeyPermissions();
        $permissionsStep->run();

        $command = $this->assembleCommand($host);

        if ($input->getOption('tunnel-via')) {
            $tunnelHost = $this->getHostForEnvironment(
                $input->getOption('tunnel-via'),
                $input->getOption('tunnel-via-hostname')
            );

            $command = $this->assembleCommand($tunnelHost, '-t', $command);
        }

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

    private function assembleCommand(Host $host, $additionalFlags = '', $command = null)
    {
        return sprintf(
            'ssh -p %s %s %s %s@%s %s',
            escapeshellarg($host->getSshPort()),
            $additionalFlags,
            ($host->getSshPrivateKey() ? '-i ' . escapeshellarg($host->getSshPrivateKey()) : ''),
            escapeshellarg($host->getUsername()),
            escapeshellarg($host->getHostname()),
            (null === $command ? '' : escapeshellarg($command))
        );
    }
}
