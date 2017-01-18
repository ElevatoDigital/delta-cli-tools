<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Config\Database\DatabaseInterface;
use DeltaCli\Host;
use DeltaCli\Log\DewdropActivityLog as DewdropActivityLogObject;

class DewdropActivityLog extends AbstractDatabaseLog
{
    public function logIsPresent(DatabaseInterface $database)
    {
        return in_array('dewdrop_activity_log', $database->getTableNames());
    }

    public function createLogObject(Host $host, DatabaseInterface $database)
    {
        return new DewdropActivityLogObject($host, $database);
    }
}
