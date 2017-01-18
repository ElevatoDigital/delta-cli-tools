<?php

namespace DeltaCli\Config;

use DeltaCli\Config\Database\DatabaseInterface;

class Config
{
    /**
     * @var DatabaseInterface[]
     */
    private $databases = [];

    public function addDatabase(DatabaseInterface $database)
    {
        $this->databases[] = $database;

        return $this;
    }

    /**
     * @return DatabaseInterface[]
     */
    public function getDatabases()
    {
        return $this->databases;
    }
}
