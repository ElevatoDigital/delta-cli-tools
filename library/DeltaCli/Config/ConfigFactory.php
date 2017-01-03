<?php

namespace DeltaCli\Config;

use DeltaCli\Config\Detector\DetectorSet;
use DeltaCli\Exec;
use DeltaCli\FileTransferPaths;
use DeltaCli\Host;
use DeltaCli\Script\Step\Scp;
use DeltaCli\SshTunnel;

class ConfigFactory
{
    /**
     * @var DetectorSet
     */
    private $detectorSet;

    public function __construct(DetectorSet $detectorSet = null)
    {
        $this->detectorSet = ($detectorSet ?: new DetectorSet);
    }

    /**
     * @param Host $host
     * @return Config[]|bool
     */
    public function detectConfigsOnHost(Host $host)
    {
        $configs = false;
        $tunnel = $host->getSshTunnel();

        $tunnel->setUp();

        $temporaryFile = null;

        foreach ($this->detectorSet->getAll() as $detector) {
            foreach ($detector->getPotentialFilePaths() as $potentialFilePath) {
                if ($this->fileExists($tunnel, $potentialFilePath)) {
                    $temporaryFile = $this->copyToTemporaryFile($tunnel, $host, $potentialFilePath);

                    $configs[] = $detector->createConfigFromFile($host->getEnvironment(), $temporaryFile);
                    break;
                }
            }
        }

        if ($temporaryFile && file_exists($temporaryFile)) {
            unlink($temporaryFile);
        }

        $tunnel->tearDown();

        return (count($configs) === 0 ? false : $configs);
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

    private function copyToTemporaryFile(SshTunnel $tunnel, Host $host, $remotePath)
    {
        $temporaryFilePath = sys_get_temp_dir() . '/' . uniqid('delta-cli-config', true);

        $scp = new Scp($temporaryFilePath, $remotePath, FileTransferPaths::DOWN);
        $scp->runOnHostViaAlreadyConfiguredSshTunnel($tunnel, $host);

        return $temporaryFilePath;
    }
}
