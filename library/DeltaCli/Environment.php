<?php

namespace DeltaCli;

use DeltaCli\Exception\EnvironmentNotFound;
use DeltaCli\Exception\HostNotFound;
use DeltaCli\Exception\MustSpecifyHostnameForShell;

class Environment
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $sshPrivateKey;

    /**
     * @var array
     */
    private $hosts = [];

    /**
     * @var string
     */
    private $gitBranch;

    /**
     * @var Host
     */
    private $tunnelHost;

    /**
     * @var bool
     */
    private $isDevEnvironment = false;

    public function __construct(Project $project, $name)
    {
        $this->project = $project;
        $this->name    = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setSshPrivateKey($sshPrivateKey)
    {
        $this->sshPrivateKey = $sshPrivateKey;

        return $this;
    }

    public function getSshPrivateKey()
    {
        return $this->sshPrivateKey;
    }

    public function setIsDevEnvironment($isDevEnvironment)
    {
        $this->isDevEnvironment = $isDevEnvironment;

        return $this;
    }

    public function isDevEnvironment()
    {
        return $this->isDevEnvironment;
    }

    public function getIsDevEnvironment()
    {
        return $this->isDevEnvironment;
    }

    public function addHost($hostname, $username = null, $sshPrivateKey = null)
    {
        $host = new Host($hostname, $this);

        $host
            ->setUsername($username)
            ->setSshPrivateKey($sshPrivateKey);

        $this->hosts[] = $host;

        return $this;
    }

    public function getHosts()
    {
        return $this->hosts;
    }

    public function getHost($hostname)
    {
        /* @var $host Host */
        foreach ($this->hosts as $host) {
            if ($host->getHostname() === $hostname) {
                return $host;
            }
        }

        return false;
    }

    public function setGitBranch($gitBranch)
    {
        $this->gitBranch = $gitBranch;

        return $this;
    }

    public function getGitBranch()
    {
        return $this->gitBranch;
    }

    public function getTunnelHost()
    {
        return $this->tunnelHost;
    }

    public function tunnelSshVia($environment, $hostname = null)
    {
        if (is_string($environment)) {
            $environment = $this->project->getEnvironment($environment);
        }

        if (!$environment instanceof Environment) {
            throw new EnvironmentNotFound("Invalid environment provided for SSH tunneling.");
        }

        $hosts = $environment->getHosts();

        if (1 === count($hosts)) {
            $this->tunnelHost = current($hosts);
        } elseif (!$hostname) {
            throw new MustSpecifyHostnameForShell(
                'When tunneling via an environment with more than one host, you must specify a host when '
                . 'calling tunnelSshVia().'
            );
        } else {
            $host = $environment->getHost($hostname);

            if (false === $host) {
                throw new HostNotFound(
                    "No host named {$hostname} could be found on the {$environment->getName()} environment."
                );
            }

            $this->tunnelHost = $host;
        }

        return $this;
    }
}
