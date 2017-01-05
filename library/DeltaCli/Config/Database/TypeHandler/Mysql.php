<?php

namespace DeltaCli\Config\Database\TypeHandler;

use PDO;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

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

    public function renderShellHelp(OutputInterface $output)
    {
        $table = new Table($output);

        $table->addRows(
            [
                ['Display Tables', 'SHOW TABLES;'],
                ['Display Columns from a Table', 'SHOW COLUMNS FROM [table-name];'],
                ['Use Expanded Output Formatting', 'Append \G to the end of your query.'],
                ['Open Editor for More Complex Queries', '\e'],
                ['Server Status', '\s'],
                ['Display Help', 'help'],
                ['Turn on Timing of All Commands', '"SET profiling=1" and "SHOW PROFILES;"'],
                ['Quit', '\q']
            ]
        );

        $table->render();
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
