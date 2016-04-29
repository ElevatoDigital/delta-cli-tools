<?php

namespace DeltaCli;

class Environment
{
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

    public function __construct($name)
    {
        $this->name = $name;
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
}
