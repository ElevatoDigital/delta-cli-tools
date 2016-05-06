<?php

namespace DeltaCli\Command;

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
        $environment = $this->project->getEnvironment($input->getArgument('environment'));
        $hosts       = $environment->getHosts();
        $selected    = null;

        if (1 === count($hosts)) {
            $selected = current($hosts);
        } else {
            $hostname = $input->getOption('hostname');

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

        passthru(
            sprintf(
                'ssh -p %s %s %s@%s',
                escapeshellarg($selected->getSshPort()),
                ($selected->getSshPrivateKey() ? '-i ' . escapeshellarg($selected->getSshPrivateKey()) : ''),
                escapeshellarg($selected->getUsername()),
                escapeshellarg($selected->getHostname())
            )
        );
    }
}
