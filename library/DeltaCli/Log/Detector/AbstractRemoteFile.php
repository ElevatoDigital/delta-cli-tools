<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Exec;
use DeltaCli\Host;
use DeltaCli\Log\File as FileLog;
use DeltaCli\SshTunnel;

abstract class AbstractRemoteFile implements DetectorInterface
{
    abstract public function getName();

    abstract public function getRemotePath();

    abstract public function getWatchByDefault();

    public function detectLogOnHost(Host $host)
    {
        $log = false;

        $sshTunnel = $host->getSshTunnel();

        $sshTunnel->setUp();

        if ($this->fileExists($sshTunnel, $this->getRemotePath())) {
            $log = new FileLog($host, $this->getName(), $this->getRemotePath(), $this->getWatchByDefault());
        }

        $sshTunnel->tearDown();

        return $log;
    }

    private function fileExists(SshTunnel $sshTunnel, $remotePath)
    {
        $command = sprintf('ls %s 2>&1', escapeshellarg($remotePath));

        Exec::run(
            $sshTunnel->assembleSshCommand($command),
            $output,
            $exitStatus
        );

        return 0 === $exitStatus;
    }
}
