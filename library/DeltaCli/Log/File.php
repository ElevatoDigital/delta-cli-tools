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

    public function __construct(Host $host, $name, $remotePath, $watchByDefault)
    {
        $this->host           = $host;
        $this->name           = $name;
        $this->remotePath     = $remotePath;
        $this->watchByDefault = $watchByDefault;
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
                        echo $output->writeln($processOutput);
                    }
                );
            }
        );
    }

    public function assembleTailCommand(SshTunnel $sshTunnel)
    {
        return $sshTunnel->assembleSshCommand(
            sprintf(
                'tail -f %s',
                escapeshellarg($this->getRemotePath())
            )
        );
    }
}
