<?php

namespace DeltaCli\Config;

use DeltaCli\Config\Database\TypeHandler\TypeHandlerFactory;

class Database
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $databaseName;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $host;

    /**
     * @var integer
     */
    private $port;

    /**
     * @var \DeltaCli\Config\Database\TypeHandler\TypeHandlerInterface
     */
    private $typeHandler;

    public function __construct($type, $databaseName, $username, $password, $host, $port = null)
    {
        $this->type         = $type;
        $this->databaseName = $databaseName;
        $this->username     = $username;
        $this->password     = $password;
        $this->host         = $host;
        $this->typeHandler  = TypeHandlerFactory::createInstance($this->type);
        $this->port         = (null === $port ? $this->getDefaultPort() : $port);
    }

    public function getType()
    {
        return $this->typeHandler->getName();
    }

    public function getDatabaseName()
    {
        return $this->databaseName;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getShellCommand($hostname = null, $port = null)
    {
        return $this->typeHandler->getShellCommand(
            $this->username,
            $this->password,
            ($hostname ?: $this->host),
            $this->databaseName,
            ($port ?: $this->port)
        );
    }

    public function getDumpCommand($hostname = null, $port = null)
    {
        return $this->typeHandler->getDumpCommand(
            $this->username,
            $this->password,
            ($hostname ?: $this->host),
            $this->databaseName,
            ($port ?: $this->port)
        );
    }

    private function getDefaultPort()
    {
        return $this->typeHandler->getDefaultPort();
    }
}
