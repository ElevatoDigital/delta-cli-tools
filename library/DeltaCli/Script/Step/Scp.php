<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Exec;
use DeltaCli\Host;
use DeltaCli\SshTunnel;

class Scp extends EnvironmentHostsStepAbstract
{
    /**
     * @const
     */
    const UP = 'upload';

    /**
     * @const
     */
    const DOWN = 'download';

    /**
     * @var string
     */
    private $localFile;

    /**
     * @var string
     */
    private $remoteFile;

    /**
     * @var string
     */
    private $direction = self::UP;

    /**
     * @var boolean
     */
    private $isDirectory = null;

    public function __construct($localFile, $remoteFile, $direction = self::UP)
    {
        $this->localFile  = $localFile;
        $this->remoteFile = $remoteFile;
        $this->direction  = $direction;
    }

    public function setIsDirectory($isDirectory)
    {
        $this->isDirectory = $isDirectory;

        return $this;
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        } else {
            return sprintf(
                'scp-%s-%s-remote',
                basename($this->localFile),
                (self::UP === $this->direction ? 'to' : 'from')
            );
        }
    }

    public function runOnHost(Host $host)
    {
        $tunnel = $host->getSshTunnel();

        $tunnel->setUp();

        $fileParts = [
            escapeshellarg($this->localFile),
            $this->getRemoteFileSpecification($tunnel, $this->remoteFile)
        ];

        if (self::DOWN === $this->direction) {
            rsort($fileParts);
        }

        $command = sprintf(
            'scp %s -C -P %s %s %s %s %s 2>&1',
            ($host->getSshPrivateKey() ? '-i ' . escapeshellarg($host->getSshPrivateKey()) : ''),
            escapeshellarg($tunnel->getPort()),
            $this->getDirectoryFlag(),
            $tunnel->getSshOptions(),
            $fileParts[0],
            $fileParts[1]
        );

        Exec::run($command, $output, $exitStatus);

        $tunnel->tearDown();

        return [$output, $exitStatus];
    }

    private function getDirectoryFlag()
    {
        if (null !== $this->isDirectory) {
            return ($this->isDirectory ? '-r' : '');
        } else {
            return is_dir($this->localFile) ? '-r' : '';
        }
    }

    private function getRemoteFileSpecification(SshTunnel $tunnel, $remoteFile)
    {
        return sprintf(
            '%s@%s:%s',
            escapeshellarg($tunnel->getUsername()),
            escapeshellarg($tunnel->getHostname()),
            escapeshellarg($remoteFile)
        );
    }
}
