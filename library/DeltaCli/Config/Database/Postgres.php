<?php

namespace DeltaCli\Config\Database;

use DeltaCli\Exception\DatabaseQueryFailed;
use DeltaCli\Exec;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class Postgres extends DatabaseAbstract
{
    public function getShellCommand()
    {
        return sprintf(
            'PGPASSWORD=%s PGOPTIONS=\'--client-min-messages=warning\' psql -q -U %s -h %s -p %s %s',
            escapeshellarg($this->password),
            escapeshellarg($this->username),
            escapeshellarg($this->host),
            escapeshellarg($this->port),
            escapeshellarg($this->databaseName)
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

    public function getTableNames()
    {
        $tables = [];

        foreach ($this->query('\dt') as $table) {
            $tables[] = $table[1];
        }

        return $tables;
    }

    public function getColumns($tableName)
    {
        $sql = "SELECT
                a.attnum,
                n.nspname,
                c.relname,
                a.attname AS column_name,
                t.typname AS type,
                a.atttypmod,
                FORMAT_TYPE(a.atttypid, a.atttypmod) AS complete_type,
                d.adsrc AS default_value,
                a.attnotnull AS notnull,
                a.attlen AS length,
                co.contype,
                ARRAY_TO_STRING(co.conkey, ',') AS conkey
            FROM pg_attribute AS a
                JOIN pg_class AS c ON a.attrelid = c.oid
                JOIN pg_namespace AS n ON c.relnamespace = n.oid
                JOIN pg_type AS t ON a.atttypid = t.oid
                LEFT OUTER JOIN pg_constraint AS co ON (co.conrelid = c.oid
                    AND a.attnum = ANY(co.conkey) AND co.contype = 'p')
                LEFT OUTER JOIN pg_attrdef AS d ON d.adrelid = c.oid AND d.adnum = a.attnum
            WHERE a.attnum > 0 AND c.relname = %s
            ORDER BY a.attnum";

        $columns = [];

        foreach ($this->query($sql, [$tableName]) as $columnData) {
            $columns[] = [
                'name' => $columnData['column_name'],
                'type' => $columnData['type']
            ];
        }

        return $columns;
    }

    public function getForeignKeys($tableName)
    {
        $sql = "SELECT
                    tc.constraint_name, tc.table_name, kcu.column_name,
                    ccu.table_name AS foreign_table_name,
                    ccu.column_name AS foreign_column_name
                FROM information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu
                    ON tc.constraint_name = kcu.constraint_name
                JOIN information_schema.columns AS co
                    ON co.table_name = tc.table_name
                        AND kcu.column_name = co.column_name
                JOIN information_schema.constraint_column_usage AS ccu
                    ON ccu.constraint_name = tc.constraint_name
                WHERE
                    constraint_type = 'FOREIGN KEY'
                    AND tc.table_name = %s
                ORDER BY co.ordinal_position;";

        $references = [];

        foreach ($this->query($sql, [$tableName]) as $row) {
            $column = $row['column_name'];

            $references[$column] = array(
                'table'  => $row['foreign_table_name'],
                'column' => $row['foreign_column_name']
            );
        }

        return $references;
    }

    public function getType()
    {
        return 'postgres';
    }

    public function getDumpCommand()
    {
        return sprintf(
            'PGPASSWORD=%s pg_dump -U %s -h %s -p %s %s',
            escapeshellarg($this->password),
            escapeshellarg($this->username),
            escapeshellarg($this->host),
            escapeshellarg($this->port),
            escapeshellarg($this->databaseName)
        );
    }

    public function emptyDb()
    {
        $this->query('DROP SCHEMA public CASCADE;');
        $this->query('CREATE SCHEMA public;');
    }

    public function query($sql, array $params = [])
    {
        $sql = $this->escapeQueryParams($sql, $params);

        $command = sprintf(
            'echo %s | %s -v ON_ERROR_STOP=1 --pset=footer -A -q 2>&1',
            escapeshellarg($sql),
            $this->getShellCommand()
        );

        Exec::run($this->sshTunnel->assembleSshCommand($command), $output, $exitStatus);

        if ($exitStatus) {
            throw new DatabaseQueryFailed(implode(PHP_EOL, $output));
        }

        if (!isset($output[0]) || !trim($output[0])) {
            return [];
        }

        return $this->prepareResultsArrayFromCommandOutput($output, "|");
    }

    public function getDefaultPort()
    {
        return 5432;
    }


    private function escapeQueryParams($sql, array $params)
    {
        $params = array_map(
            function ($value) {
                return sprintf("'%s'", str_replace("'", "''", $value));
            },
            $params
        );

        return vsprintf($sql, $params);
    }
}
