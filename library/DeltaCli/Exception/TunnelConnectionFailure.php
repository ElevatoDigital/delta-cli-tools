<?php

namespace DeltaCli\Exception;

use DeltaCli\Host;
use Exception as PhpException;
use Symfony\Component\Console\Output\OutputInterface;

class TunnelConnectionFailure extends PhpException implements ConsoleOutputInterface
{
    /**
     * @var Host
     */
    private $host;

    public function hasBanner()
    {
        return false;
    }

    public function setHost(Host $host)
    {
        $this->host = $host;

        return $this;
    }

    public function outputToConsole(OutputInterface $output)
    {
        $output->writeln(
            [
                'We could not connect to the environment selected for SSH tunneling.  You probably need to',
                'install your project\'s SSH key in the environment to continue.  You will need the password for',
                'the user account in the remote environment to install the key.',
                ''
            ]
        );

        $environmentName = 'vpn';

        if ($this->host) {
            $environmentName = $this->host->getEnvironment()->getName();

            $output->writeln(
                [
                    'You are attempting to tunnel via the following environment and host:',
                    '  <comment>Environment:</comment> ' . $environmentName,
                    '  <comment>Hostname:</comment> ' . $this->host->getHostname(),
                    '  <comment>Username:</comment> ' . $this->host->getUsername(),
                    '  <comment>Port:</comment> ' . $this->host->getSshPort(),
                    ''
                ]
            );
        }

        $output->writeln(
            [
                'Install SSH keys in an environment:',
                "  <fg=cyan>delta ssh:install-key {$environmentName}</>"
            ]
        );
    }
}
