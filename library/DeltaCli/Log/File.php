<?php

namespace DeltaCli\Log;

use DeltaCli\Host;
use DeltaCli\SshTunnel;
use React\EventLoop\LoopInterface;
use React\ChildProcess\Process as ChildProcess;
use React\EventLoop\Timer\TimerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class File implements LogInterface
{
    /**
     * @var Host
     */
    private $host;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $remotePath;

    /**
     * @var boolean
     */
    private $watchByDefault;

    /**
     * @var bool
     */
    private $requiresRoot = false;

    public function __construct(Host $host, $name, $remotePath, $watchByDefault)
    {
        $this->host           = $host;
        $this->name           = $name;
        $this->remotePath     = $remotePath;
        $this->watchByDefault = $watchByDefault;
    }

    public function setRequiresRoot($requiresRoot)
    {
        $this->requiresRoot = $requiresRoot;

        return $this;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getRemotePath()
    {
        return $this->remotePath;
    }

    public function getDescription()
    {
        return $this->getRemotePath();
    }

    public function getWatchByDefault()
    {
        return $this->watchByDefault;
    }

    public function attachToEventLoop(LoopInterface $loop, OutputInterface $output)
    {
        $sshTunnel = $this->host->getSshTunnel();

        $sshTunnel->setUp();

        $process = new ChildProcess($this->assembleTailCommand($sshTunnel));

        $process->on(
            'exit',
            function () use ($output) {
                $output->writeln('Process exited.');
            }
        );

        $loop->addTimer(
            0.001,
            function (TimerInterface $timer) use ($process, $output) {
                $process->start($timer->getLoop());

                $process->stdout->on(
                    'data',
                    function ($processOutput) use ($output) {
                        $output->writeln($this->assembleOutputLine($processOutput));
                    }
                );
            }
        );
    }

    private function assembleTailCommand(SshTunnel $sshTunnel)
    {
        return $sshTunnel->assembleSshCommand(
            sprintf(
                '%stail -F %s',
                ($this->requiresRoot ? 'sudo ' : ''),
                escapeshellarg($this->getRemotePath())
            )
        );
    }

    private function assembleOutputLine($processOutput)
    {
        $logInfo = sprintf(
            '<fg=blue>%s <%s></>',
            $this->getName(),
            $this->host->getHostname()
        );

        return [$logInfo, trim($processOutput)];
    }
}
