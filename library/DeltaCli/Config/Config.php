<?php

namespace DeltaCli\Config;

class Config
{
    /**
     * @var Database[]
     */
    private $databases = [];

    public function addDatabase(Database $database)
    {
        $this->databases[] = $database;

        return $this;
    }

    /**
     * @return Database[]
     */
    public function getDatabases()
    {
        return $this->databases;
    }
}
