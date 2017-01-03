<?php

namespace DeltaCli\Config\Database\TypeHandler;

use PDO;

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

    public function getDumpCommand($username, $password, $hostname, $databaseName, $port)
    {
        return sprintf(
            'PGPASSWORD=%s pg_dump -U %s -h %s -p %s %s',
            escapeshellarg($password),
            escapeshellarg($username),
            escapeshellarg($hostname),
            escapeshellarg($port),
            escapeshellarg($databaseName)
        );
    }

    public function createPdoConnection($username, $password, $hostname, $databaseName, $port)
    {
        $dsn = sprintf(
            'pgsql:dbname=%s;host=%s;port=%s',
            $databaseName,
            $hostname,
            $port
        );

        return new PDO($dsn, $username, $password);
    }

    public function emptyDb(PDO $pdo)
    {
        $pdo->query('DROP SCHEMA public CASCADE;');
        $pdo->query('CREATE SCHEMA public;');
    }

    public function getDefaultPort()
    {
        return 5432;
    }
}
