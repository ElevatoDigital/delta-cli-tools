<?php

namespace DeltaCli;

use DeltaCli\Exception\TunnelConnectionFailure as TunnelConnectionFailureException;

class SshTunnel
{
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
     * @var int
     */
    private $sshProcess;

    /**
     * @var bool
     */
    private $batchMode = true;

    public function __construct(Host $host)
    {
        $this->host = $host;
    }

    public function setBatchMode($batchMode)
    {
        $this->batchMode = $batchMode;

        return $this;
    }

    public function tunnelConnectionsForHost(Host $host, $tunnelUsername)
    {
        $this->tunnelConnectionsForHost = $host;
        $this->tunnelUsername           = ($tunnelUsername ?: $host->getUsername());

        return $this;
    }

    public function getPort()
    {
        if ($this->host->getTunnelHost()) {
            return $this->host->getTunnelHost()->getSshTunnel()->getPort();
        } else {
            return $this->tunnelPort ?: $this->host->getSshPort();
        }
    }

    public function getUsername()
    {
        if ($this->host->getTunnelHost()) {
            return $this->host->getTunnelHost()->getSshTunnel()->getUsername();
        } else {
            return $this->tunnelUsername ?: $this->host->getUsername();
        }
    }

    public function getHostname()
    {
        if ($this->host->getTunnelHost()) {
            return $this->host->getTunnelHost()->getSshTunnel()->getHostname();
        } else {
            return $this->tunnelPort ? 'localhost' : $this->host->getHostname();
        }
    }

    public function getCommand()
    {
        if ($this->host->getTunnelHost()) {
            return $this->host->getTunnelHost()->getSshTunnel()->getCommand();
        } else {
            return sprintf(
                'ssh -p %d -i %s %s',
                $this->getPort(),
                escapeshellarg($this->host->getSshPrivateKey()),
                $this->getSshOptions($this->host)
            );
        }
    }

    public function getSshOptions(Host $host)
    {
        $options = [];

        if ($this->batchMode) {
            $options['BatchMode'] = 'yes';
        }

        if ('localhost' === $this->getHostname()) {
            $options['UserKnownHostsFile']    = '/dev/null';
            $options['StrictHostKeyChecking'] = 'no';
            $options['LogLevel']              = 'error';
        }

        if ($host) {
            foreach ($host->getAdditionalSshOptions() as $option => $value) {
                $options[$option] = $value;
            }
        }

        return $this->assembleSshOptionsString($options);
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

            $keyFlag = '';

            if ($this->host->getSshPrivateKey()) {
                $keyFlag = '-i ' . escapeshellarg($this->host->getSshPrivateKey());
            }

            $command = sprintf(
                'ssh %s -o BatchMode=yes -p %s %s@%s -L %d:%s:22 -N > /dev/null 2>&1 & echo $!',
                $keyFlag,
                escapeshellarg($this->host->getSshPort()),
                escapeshellarg($this->host->getUsername()),
                escapeshellarg($this->host->getHostname()),
                $this->tunnelPort,
                $this->tunnelConnectionsForHost->getHostname()
            );

            Debug::log("Opening SSH tunnel with `{$command}`...");

            $this->sshProcess = trim(shell_exec($command));

            $this->waitUntilTunnelIsOpen();

            if (false === posix_getpgid($this->sshProcess)) {
                $exception = new TunnelConnectionFailureException('Failed to connect to SSH tunnel environment.');
                $exception->setHost($this->host);
                throw $exception;
            }
        }
    }

    public function tearDown()
    {
        if ($this->host->getTunnelHost()) {
            $this->host->getTunnelHost()->getSshTunnel()->tearDown();
        }

        if ($this->sshProcess) {
            Debug::log(
                "Tearing down SSH tunnel for {$this->tunnelConnectionsForHost->getHostname()} with PID "
                . "{$this->sshProcess}."
            );

            $success = posix_kill($this->sshProcess, 9);

            if ($success) {
                Debug::log("Successfully killed SSH tunnel process {$this->sshProcess}.");
            } else {
                Debug::log("Failed to kill SSH tunnel process {$this->sshProcess}.");
            }

            $this->sshProcess = null;
        }
    }

    public function assembleSshCommand($command = null, $additionalFlags = '', $includeApplicationEnv = true)
    {
        $keyFlag = '';

        if ($this->host->getSshPrivateKey()) {
            $keyFlag = '-i ' . escapeshellarg($this->host->getSshPrivateKey());
        }

        if (null !== $command) {
            $command = sprintf(
                '%s%s',
                ($includeApplicationEnv ? $this->getApplicationEnvVar() : ''),
                $command
            );

            if ($this->host->getSshHomeFolder()) {
                $command = sprintf(
                    'cd %s; %s',
                    escapeshellarg($this->host->getSshHomeFolder()),
                    $command
                );
            }
        }

        $command = sprintf(
            'ssh %s -p %s %s %s %s@%s %s',
            $this->getSshOptions($this->host),
            escapeshellarg($this->getPort()),
            $additionalFlags,
            $keyFlag,
            escapeshellarg($this->getUsername()),
            escapeshellarg($this->getHostname()),
            (null === $command ? '' : escapeshellarg($command))
        );

        if ($this->host->getSshPassword()) {
            $command = $this->wrapCommandInExpectScript($command, $this->host->getSshPassword());
        }

        return $command;
    }

    public function wrapCommandInExpectScript($command, $password)
    {
        return sprintf(
            __DIR__ . '/_files/ssh-with-password.exp %s %s > /dev/null 2>&1',
            $password,
            $command
        );
    }

    public function getApplicationEnvVar()
    {
        return sprintf('export APPLICATION_ENV=%s; ', $this->host->getEnvironment()->getApplicationEnv());
    }

    private function waitUntilTunnelIsOpen()
    {
        $waitedIterations = 0;

        while (!$this->someoneAlreadyListeningOnPort($this->tunnelPort)) {
            usleep(500);

            $waitedIterations += 1;

            if (100 < $waitedIterations) {
                break;
            }
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
        $connection = @stream_socket_client("tcp://localhost:{$portNumber}", $errorNumber, $errorString);

        if ($errorString) {
            return false;
        }

        fclose($connection);

        return true;
    }

    private function assembleSshOptionsString(array $sshOptions)
    {
        $optionStrings = [];

        foreach ($sshOptions as $option => $value) {
            $optionStrings[] = sprintf('-o %s=%s', $option, $value);
        }

        return implode(' ', $optionStrings);
    }
}
