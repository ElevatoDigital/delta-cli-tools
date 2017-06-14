<?php

namespace DeltaCli\Log;

class DewdropActivityLog extends AbstractDatabaseLog
{
    public function getName()
    {
        return 'dewdrop-activity-log';
    }

    public function getDescription()
    {
        return "dewdrop_activity_log table of the {$this->getDatabase()->getDatabaseName()} database.";
    }

    public function assembleSql($afterId = null)
    {
        $whereClause = '';
        $limitClause = '';

        $params = [];

        if (null === $afterId) {
            $limitClause = 'LIMIT 10';
        } else {
            $whereClause = 'WHERE dewdrop_activity_log_id > %s';
            $params[]    = $afterId;
        }

        $sql = "SELECT 
                delta_log_id AS id, 
                {$this->getDatabase()->getReplaceNewlinesExpression('message')} AS message, 
                date_created 
            FROM dewdrop_activity_log
            {$whereClause}
            ORDER BY dewdrop_activity_log_id DESC
            {$limitClause}";

        return ['sql' => $sql, 'params' => $params];
    }
}