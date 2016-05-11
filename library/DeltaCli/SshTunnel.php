<?php

namespace DeltaCli;

class SshTunnel
{
    private static $procOpenPipeDescriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];

    /**
     * @var Host
     */
    private $host;

    /**
     * @var Host
     */
    private $tunnelConnectionsForHost;

    /**
     * @var string
     */
    private $tunnelUsername;

    /**
     * @var integer
     */
    private $tunnelPort;

    /**
     * @var resource
     */
    private $sshProcess;

    /**
     * @var array
     */
    private $sshPipes;

    public function __construct(Host $host)
    {
        $this->host = $host;
    }

    public function tunnelConnectionsForHost(Host $host, $tunnelUsername)
    {
        $this->tunnelConnectionForHost = $host;
        $this->tunnelUsername          = ($tunnelUsername ?: $host->getUsername());

        return $this;
    }

    public function getPort()
    {
        return $this->tunnelPort ?: $this->host->getSshPort();
    }

    public function getUsername()
    {
        return $this->tunnelUsername ?: $this->host->getUsername();
    }

    public function getHostname()
    {
        return $this->tunnelPort ? 'localhost' : $this->host->getHostname();
    }

    public function getCommand()
    {
        if ($this->host->getTunnelHost()) {
            return $this->host->getTunnelHost()->getSshTunnel()->getCommand();
        } else {
            return sprintf('ssh -p %d', $this->getPort());
        }
    }

    public function setUp()
    {
        if ($this->host->getTunnelHost()) {
            $tunnel = $this->host->getTunnelHost()->getSshTunnel();
            $tunnel->tunnelConnectionsForHost($this->host, $this->tunnelUsername);
            $tunnel->setUp();
        }

        if ($this->tunnelConnectionsForHost) {
            $this->tunnelPort = $this->findAvailableLocalPort();

            $command = sprintf(
                'ssh -f %s@%s -L %d:%s:22 -N',
                escapeshellarg($this->host->getUsername()),
                escapeshellarg($this->host->getHostname()),
                $this->tunnelPort,
                $this->tunnelConnectionsForHost->getHostname()
            );

            Debug::log("Opening SSH tunnel with `{$command}`...");

            $this->sshProcess = proc_open($command, self::$procOpenPipeDescriptors, $pipes);
            $this->sshPipes   = $pipes;
        }
    }

    public function tearDown()
    {
        if ($this->host->getTunnelHost()) {
            $this->host->getTunnelHost()->getSshTunnel()->tearDown();
        }

        if ($this->sshProcess) {
            Debug::log("Tearing down SSH tunnel for {$this->tunnelConnectionsForHost->getHostname()}.");

            fclose($this->sshPipes[0]);
            fclose($this->sshPipes[1]);
            fclose($this->sshPipes[2]);

            proc_close($this->sshProcess);
        }
    }

    private function findAvailableLocalPort()
    {
        $availablePortNumber = false;

        while (!$availablePortNumber) {
            $potentialPortNumber = mt_rand(1025, 65535);

            if (!$this->someoneAlreadyListeningOnPort($potentialPortNumber)) {
                $availablePortNumber = $potentialPortNumber;
            }
        }

        return $availablePortNumber;
    }

    private function someoneAlreadyListeningOnPort($portNumber)
    {
        $connection = stream_socket_client("tcp://localhost:{$portNumber}", $errorNumber, $errorString);

        if ($errorString) {
            return true;
        }

        fclose($connection);

        return false;
    }
}
