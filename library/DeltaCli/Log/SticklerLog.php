<?php

namespace DeltaCli\Log;

class SticklerLog extends AbstractDatabaseLog
{
    public function getName()
    {
        return 'stickler-log';
    }

    public function getDescription()
    {
        return "delta_log table of the {$this->getDatabase()->getDatabaseName()} database.";
    }

    public function assembleSql($afterId = null)
    {
        $whereClause = '';
        $limitClause = '';

        $params = [];

        if (null === $afterId) {
            $limitClause = 'LIMIT 10';
        } else {
            $whereClause = 'WHERE delta_log_id > %s';
            $params[]    = $afterId;
        }

        $sql = "SELECT 
                delta_log_id AS id, 
                {$this->getDatabase()->getReplaceNewlinesExpression('message')} AS message, 
                date_created 
            FROM delta_log
            {$whereClause}
            ORDER BY delta_log_id DESC
            {$limitClause}";

        return ['sql' => $sql, 'params' => $params];
    }
}