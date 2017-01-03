<?php

namespace DeltaCli\Config;

use DeltaCli\Exception\InvalidDatabaseType as InvalidDatabaseTypeException;

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

    public function __construct($type, $databaseName, $username, $password, $host, $port = null)
    {
        $this->validateType($type);

        $this->type         = $type;
        $this->databaseName = $databaseName;
        $this->username     = $username;
        $this->password     = $password;
        $this->host         = $host;
        $this->port         = (null === $port ? $this->getDefaultPort() : $port);
    }

    public function getType()
    {
        return $this->type;
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
        if ('postgres' === $this->type) {
            return sprintf(
                'PGPASSWORD=%s psql -U %s -h %s -p %d %s',
                escapeshellarg($this->password),
                escapeshellarg($this->username),
                escapeshellarg($hostname ?: $this->host),
                escapeshellarg($port ?: $this->port),
                escapeshellarg($this->databaseName)
            );
        } else {
            return sprintf(
                'mysql --user=%s --password=%s --host=%s --port=%d %s',
                escapeshellarg($this->username),
                escapeshellarg($this->password),
                escapeshellarg($hostname ?: $this->host),
                escapeshellarg($port ?: $this->port),
                escapeshellarg($this->databaseName)
            );
        }
    }

    private function validateType($type)
    {
        if (!in_array($type, ['postgres', 'mysql'])) {
            throw new InvalidDatabaseTypeException("Database type must be postgres or mysql.  Received '{$type}'.");
        }
    }

    private function getDefaultPort()
    {
        if ('postgres' === $this->type) {
            return 5432;
        } else {
            return 3306;
        }
    }
}
