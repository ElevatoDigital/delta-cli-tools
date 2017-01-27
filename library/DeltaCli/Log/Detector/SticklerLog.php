<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Config\Database\DatabaseInterface;
use DeltaCli\Host;
use DeltaCli\Log\SticklerLog as SticklerLogObject;

class SticklerLog extends AbstractDatabaseLog
{
    public function logIsPresent(DatabaseInterface $database)
    {
        return 'postgres' === $database->getType() && in_array('delta_log', $database->getTableNames());
    }

    public function createLogObject(Host $host, DatabaseInterface $database)
    {
        return new SticklerLogObject($host, $database);
    }

    public function getName()
    {
        return 'stickler-log';
    }
}
