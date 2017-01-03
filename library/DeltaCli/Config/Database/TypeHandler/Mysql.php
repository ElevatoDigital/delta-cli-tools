<?php

namespace DeltaCli\Config\Database\TypeHandler;

use PDO;

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

    public function getDumpCommand($username, $password, $hostname, $databaseName, $port)
    {
        return sprintf(
            'mysqldump --user=%s --password=%s --host=%s --port=%s %s',
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($hostname),
            escapeshellarg($port),
            escapeshellarg($databaseName)
        );
    }

    public function createPdoConnection($username, $password, $hostname, $databaseName, $port)
    {
        $dsn = sprintf(
            'mysql:dbname=%s;host=%s;port=%s',
            $databaseName,
            $hostname,
            $port
        );

        return new PDO($dsn, $username, $password);
    }

    public function emptyDb(PDO $pdo)
    {
        foreach ($pdo->query('SHOW TABLES') as $tableName) {
            $pdo->query("DROP TABLE {$tableName}");
        }
    }

    public function getDefaultPort()
    {
        return 3306;
    }

}
