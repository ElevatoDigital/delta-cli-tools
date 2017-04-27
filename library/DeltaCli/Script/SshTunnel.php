<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;

class SshTunnel extends Script
{
    private $remotePort = 22;

    private $localPort;

    private $hostname;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'ssh:tunnel',
            'Create an SSH tunnel to a remote environment.'
        );
    }

    protected function configure()
    {
        $this->requireEnvironment();

        $this->addSetterOption(
            'hostname',
            null,
            InputOption::VALUE_REQUIRED,
            'The specific host you would like to connect to.'
        );

        $this->addSetterOption(
            'remote-port',
            null,
            InputOption::VALUE_REQUIRED,
            'The remote port you want to tunnel to.  Defaults to 22.'
        );

        $this->addSetterOption(
            'local-port',
            null,
            InputOption::VALUE_REQUIRED,
            'The local port you want to use to connect to the tunnel.  Random by default.'
        );

        parent::configure();
    }

    public function setHostname($hostname)
    {
        $this->hostname = $hostname;

        return $this;
    }

    public function setRemotePort($remotePort)
    {
        $this->remotePort = $remotePort;

        return $this;
    }

    public function setLocalPort($localPort)
    {
        $this->localPort = $localPort;

        return $this;
    }

    protected function addSteps()
    {
        $this->addStep(
            'open-tunnel',
            function () {
                $environment = $this->getProject()->getSelectedEnvironment();
                $host        = $environment->getSelectedHost($this->hostname);

                $host->getSshTunnel()->setRemotePort($this->remotePort);

                if ($this->localPort) {
                    $host->getSshTunnel()->setLocalPort($this->localPort);
                }

                $port = $host->getSshTunnel()->setUp();

                $output = $this->getProject()->getOutput();

                $output->writeln(
                    [
                        'You can now connect to your SSH tunnel using the following information.',
                        ''
                    ]
                );

                $table = new Table($output);

                $table->addRows(
                    [
                        ['Host', 'localhost'],
                        ['Port', $host->getSshTunnel()->getPort()],
                        ['Username', $environment->getUsername()],
                        ['SSH Key', $environment->getSshPrivateKey()]
                    ]
                );

                $table->render();

                $output->writeln(
                    [
                        '',
                        '<comment>Press enter to close the tunnel when you are ready to disconnect...</comment>'
                    ]
                );

                fgetc(STDIN);

                $host->getSshTunnel()->tearDown();
            }
        );
    }
}
