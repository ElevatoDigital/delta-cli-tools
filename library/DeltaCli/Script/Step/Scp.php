<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Console\Output\Spinner;
use DeltaCli\FileTransferPaths;
use DeltaCli\Host;
use DeltaCli\SshTunnel;

class Scp extends EnvironmentHostsStepAbstract
{
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
    private $direction = FileTransferPaths::UP;

    /**
     * @var boolean
     */
    private $isDirectory = null;

    public function __construct($localFile, $remoteFile, $direction = FileTransferPaths::UP)
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
                (FileTransferPaths::UP === $this->direction ? 'to' : 'from')
            );
        }
    }

    public function runOnHost(Host $host)
    {
        $tunnel = $host->getSshTunnel();

        $tunnel->setUp();

        $result = $this->runOnHostViaAlreadyConfiguredSshTunnel($tunnel, $host);

        $tunnel->tearDown();

        return $result;
    }

    public function runOnHostViaAlreadyConfiguredSshTunnel(SshTunnel $tunnel, Host $host)
    {
        $remoteFile = $this->remoteFile;

        if (0 !== strpos($remoteFile, '/') && $host->getSshHomeFolder()) {
            $remoteFile = rtrim($host->getSshHomeFolder(), '/') . '/' . $remoteFile;
        }

        $fileParts = [
            escapeshellarg($this->localFile),
            $this->getRemoteFileSpecification($tunnel, $remoteFile)
        ];

        if (FileTransferPaths::DOWN === $this->direction) {
            $fileParts = array_reverse($fileParts);
        }

        $command = sprintf(
            'scp %s -C -P %s %s %s %s %s',
            ($host->getSshPrivateKey() ? '-i ' . escapeshellarg($host->getSshPrivateKey()) : ''),
            escapeshellarg($tunnel->getPort()),
            $this->getDirectoryFlag(),
            $tunnel->getSshOptions($host),
            $fileParts[0],
            $fileParts[1]
        );

        if ($host->getSshPassword()) {
            $command = $tunnel->wrapCommandInExpectScript($command, $host->getSshPassword());
        }

        $this->execSsh($host, $command, $output, $exitStatus, Spinner::forStep($this, $host));

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
