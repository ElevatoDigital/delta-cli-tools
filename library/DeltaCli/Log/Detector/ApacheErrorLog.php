<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Log\LogInterface;

class ApacheErrorLog extends AbstractRemoteFile
{
    public function getName()
    {
        return 'apache-error-log';
    }

    public function getRemotePath()
    {
        return 'logs/error_log';
    }

    public function getWatchByDefault()
    {
        return LogInterface::WATCH_BY_DEFAULT;
    }
}
