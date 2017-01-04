<?php

namespace DeltaCli\Config;

use DeltaCli\Cache;
use DeltaCli\Config\Detector\DetectorInterface;
use DeltaCli\Config\Detector\DetectorSet;
use DeltaCli\Exec;
use DeltaCli\FileTransferPaths;
use DeltaCli\Host;
use DeltaCli\Script\Step\Scp;
use DeltaCli\SshTunnel;

class ConfigFactory
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var DetectorSet
     */
    private $detectorSet;

    public function __construct(Cache $cache = null, DetectorSet $detectorSet = null)
    {
        $this->cache       = $cache;
        $this->detectorSet = ($detectorSet ?: new DetectorSet);
    }

    /**
     * @param Host $host
     * @return Config[]|bool
     */
    public function detectConfigsOnHost(Host $host)
    {
        $configs = [];
        $tunnel  = $host->getSshTunnel();

        $tunnel->setUp();

        $temporaryFile = null;

        // First check previously successful detector from the cache
        if ($this->cache && $this->cache->fetch('config-detector')) {
            foreach ($this->detectorSet->getAll() as $detector) {
                if ($this->cache->fetch('config-detector') !== $detector->getName()) {
                    continue;
                }

                $config = $this->checkFilePath($detector, $tunnel, $host, $detector->getMostLikelyRemoteFilePath());

                if ($config) {
                    $configs[] = $config;
                    break;
                }

                foreach ($detector->getPotentialFilePaths() as $potentialFilePath) {
                    $config = $this->checkFilePath($detector, $tunnel, $host, $potentialFilePath);

                    if ($config) {
                        $configs[] = $config;
                        break;
                    }
                }
            }
        }

        // Then check the most likely path on each detector
        if (!count($configs)) {
            foreach ($this->detectorSet->getAll() as $detector) {
                $config = $this->checkFilePath($detector, $tunnel, $host, $detector->getMostLikelyRemoteFilePath());

                if ($config) {
                    if ($this->cache) {
                        $this->cache->store('config-detector', $detector->getName());
                    }

                    $configs[] = $config;
                    break;
                }
            }
        }

        // Then move on to the other paths only if we didn't already find a config
        if (!count($configs)) {
            foreach ($this->detectorSet->getAll() as $detector) {
                foreach ($detector->getPotentialFilePaths() as $potentialFilePath) {
                    $config = $this->checkFilePath($detector, $tunnel, $host, $potentialFilePath);

                    if ($config) {
                        $configs[] = $config;
                        break;
                    }
                }
            }
        }

        if ($temporaryFile && file_exists($temporaryFile)) {
            unlink($temporaryFile);
        }

        $tunnel->tearDown();

        return (count($configs) === 0 ? false : $configs);
    }

    private function checkFilePath(DetectorInterface $detector, SshTunnel $tunnel, Host $host, $potentialFilePath)
    {
        $config = false;

        if ($this->fileExists($tunnel, $potentialFilePath)) {
            $temporaryFile = $this->copyToTemporaryFile($tunnel, $host, $potentialFilePath);

            $config = $detector->createConfigFromFile($host->getEnvironment(), $temporaryFile);
        }

        return $config;
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
