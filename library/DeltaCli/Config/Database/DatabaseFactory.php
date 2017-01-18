<?php

namespace DeltaCli\Config\Database;

use DeltaCli\Exception\InvalidDatabaseType;

class DatabaseFactory
{
    public static function createInstance($typeName, $databaseName, $username, $password, $host)
    {
        switch ($typeName) {
            case 'postgres':
                return new Postgres($databaseName, $username, $password, $host);
            case 'mysql':
                return new Mysql($databaseName, $username, $password, $host);
            default:
                throw new InvalidDatabaseType(
                    "Database type must be postgres or mysql.  Received '{$typeName}'."
                );
        }
    }
}
