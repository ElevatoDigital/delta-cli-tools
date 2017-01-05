<?php

namespace DeltaCli\Config\Database\TypeHandler;

use DeltaCli\Exception\PgsqlPdoDriverNotInstalled;
use PDO;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

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

    public function renderShellHelp(OutputInterface $output)
    {
        $table = new Table($output);

        $table->addRows(
            [
                ['Display Tables', '\dt+'],
                ['Display Columns from a Table', '\d [table-name]'],
                ['Automatically Enable Expanded Display Mode', '\x auto'],
                ['Open Editor for More Complex Queries', '\e'],
                ['Display Help', '\?'],
                ['Turn on Timing of All Commands', '\timing'],
                ['Run Query Repeatedly and Show Results', '\watch [number-of-seconds]'],
                ['Connection Information', '\conninfo'],
                ['Quit', '\q']
            ]
        );

        $table->render();
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
        if (!extension_loaded('pdo_pgsql')) {
            throw new PgsqlPdoDriverNotInstalled('The pgsql PDO driver is not installed.');
        }

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
