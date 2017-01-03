<?php

namespace DeltaCli\Config\Database\TypeHandler;

class Mysql implements TypeHandlerInterface
{
    public function getShellCommand($username, $password, $hostname, $databaseName, $port)
    {
        return sprintf(
            'mysql --user=%s --password=%s --host=%s --port=%s %s',
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($hostname),
            escapeshellarg($port),
            escapeshellarg($databaseName)
        );
    }

    public function getName()
    {
        return 'mysql';
    }

    public function getDumpCommand()
    {
        // TODO: Implement getDumpCommand() method.
    }

    public function getDefaultPort()
    {
        return 3306;
    }

}
