<?php

namespace DeltaCli\Config\Database\TypeHandler;

use DeltaCli\Exception\InvalidDatabaseType;

class TypeHandlerFactory
{
    public static function createInstance($typeName)
    {
        switch ($typeName) {
            case 'postgres':
                return new Postgres();
            case 'mysql':
                return new Mysql();
            default:
                throw new InvalidDatabaseType(
                    "Database type must be postgres or mysql.  Received '{$typeName}'."
                );
        }
    }
}
