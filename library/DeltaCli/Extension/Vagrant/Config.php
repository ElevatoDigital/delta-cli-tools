<?php

namespace DeltaCli\Extension\Vagrant;

use DeltaCli\Cache;
use DeltaCli\Config\Config as BaseConfig;
use DeltaCli\Exec;
use DeltaCli\Host;
use DeltaCli\SshTunnel;

class Config extends BaseConfig
{
    private $detectionPerformed = false;

    /**
     * @var Host
     */
    private $host;

    public function __construct(Host $host)
    {
        $this->host     = $host;
    }

    public function hasBrowserUrl()
    {
        $this->detectBrowserUrl();

        return parent::hasBrowserUrl();
    }

    public function getBrowserUrl()
    {
        $this->detectBrowserUrl();

        return parent::getBrowserUrl();
    }

    private function detectBrowserUrl()
    {
        if ($this->detectionPerformed) {
            return $this->browserUrl;
        }

        $this->detectionPerformed = true;

        $sshTunnel = $this->host->getSshTunnel();
        $sshTunnel->setUp();

        $vhostConfigs = $this->getVhostConfigs($sshTunnel);
        $serverName   = null;

        foreach ($vhostConfigs as $configFile => $documentRoot) {
            if ($this->projectMatchesDocumentRoot($documentRoot)) {
                $serverName = $this->detectServerNameInConfig($sshTunnel, $configFile);
                break;
            }
        }

        $sshTunnel->tearDown();

        if ($serverName) {
            $this->setBrowserUrl("{$serverName}:8080");
        }

        return $this;
    }

    private function getVhostConfigs(SshTunnel $sshTunnel)
    {
        Exec::run(
            $sshTunnel->assembleSshCommand('grep -R DocumentRoot /delta/vhost.d'),
            $output,
            $exitStatus
        );

        if (0 !== $exitStatus) {
            return [];
        }

        $configs = [];

        foreach ($output as $line) {
            $configFile   = substr($line, 0, strpos($line, ':'));
            $documentRoot = trim(substr($line, strrpos($line, 'DocumentRoot') + strlen('DocumentRoot')));

            $configs[$configFile] = $documentRoot;
        }

        return $configs;
    }

    private function projectMatchesDocumentRoot($documentRoot)
    {
        return 0 === strpos($documentRoot, getcwd());
    }

    private function detectServerNameInConfig(SshTunnel $sshTunnel, $configFile)
    {
        Exec::run(
            $sshTunnel->assembleSshCommand(
                sprintf('grep ServerName %s', escapeshellarg($configFile))
            ),
            $output,
            $exitStatus
        );

        if (0 !== $exitStatus) {
            return null;
        }

        $line = reset($output);

        return trim(substr($line, strpos($line, 'ServerName') + strlen('ServerName')));
    }
}