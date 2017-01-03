<?php

namespace DeltaCli\Config\Database\TypeHandler;

class Postgres implements TypeHandlerInterface
{
    public function getShellCommand($username, $password, $hostname, $databaseName, $port)
    {
        return sprintf(
            'PGPASSWORD=%s psql -U %s -h %s -p %s %s',
            escapeshellarg($password),
            escapeshellarg($username),
            escapeshellarg($hostname),
            escapeshellarg($port),
            escapeshellarg($databaseName)
        );
    }

    public function getName()
    {
        return 'postgres';
    }

    public function getDumpCommand()
    {
        // TODO: Implement getDumpCommand() method.
    }

    public function getDefaultPort()
    {
        return 5432;
    }
}
