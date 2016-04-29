<?php

namespace DeltaCli;

class Host
{
    /**
     * @var string
     */
    private $hostname;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $sshPrivateKey;

    /**
     * @var int
     */
    private $sshPort = 22;

    public function __construct($hostname, Environment $environment)
    {
        $this->hostname    = $hostname;
        $this->environment = $environment;
    }

    public function hasRequirementsForSshUse()
    {
        return $this->getUsername();
    }

    public function getHostname()
    {
        return $this->hostname;
    }

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    public function getUsername()
    {
        return ($this->username ?: $this->environment->getUsername());
    }

    public function setSshPrivateKey($sshPrivateKey)
    {
        $this->sshPrivateKey = $sshPrivateKey;

        return $this;
    }

    public function getSshPrivateKey()
    {
        return ($this->sshPrivateKey ?: $this->environment->getSshPrivateKey());
    }

    public function setSshPort($sshPort)
    {
        $this->sshPort = $sshPort;

        return $this;
    }

    public function getSshPort()
    {
        return $this->sshPort;
    }
}
