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

    /**
     * @var string
     */
    private $sshHomeFolder;

    /**
     * @var SshTunnel
     */
    private $sshTunnel;

    /**
     * @var string
     */
    private $sshPassword;

    /**
     * @var array
     */
    private $additionalSshOptions = [];

    /**
     * @var Host
     */
    private $tunnelHost;

    public function __construct($hostname, Environment $environment)
    {
        $this->hostname    = $hostname;
        $this->environment = $environment;
    }

    public function getEnvironment()
    {
        return $this->environment;
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

    public function setSshHomeFolder($sshHomeFolder)
    {
        $this->sshHomeFolder = $sshHomeFolder;

        return $this;
    }

    public function getSshHomeFolder()
    {
        return $this->sshHomeFolder;
    }

    public function setSshPassword($sshPassword)
    {
        $this->sshPassword = $sshPassword;

        return $this;
    }

    public function getSshPassword()
    {
        return $this->sshPassword;
    }

    public function setAdditionalSshOptions($additionalSshOptions)
    {
        $this->additionalSshOptions = $additionalSshOptions;

        return $this;
    }

    public function getAdditionalSshOptions()
    {
        return $this->additionalSshOptions;
    }

    public function setTunnelHost(Host $tunnelHost)
    {
        $this->tunnelHost = $tunnelHost;

        return $this;
    }

    public function getTunnelHost()
    {
        return $this->tunnelHost ?: $this->environment->getTunnelHost();
    }

    public function getSshTunnel()
    {
        if (!$this->sshTunnel) {
            $this->sshTunnel = new SshTunnel($this);
        }

        return $this->sshTunnel;
    }

    public function createSshTunnel()
    {
        return new SshTunnel($this);
    }

    public function setSshTunnel(SshTunnel $sshTunnel)
    {
        $this->sshTunnel = $sshTunnel;

        return $this;
    }
}
