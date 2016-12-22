<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Log\LogInterface;

class ApacheAccessLog extends AbstractRemoteFile
{
    public function getName()
    {
        return 'apache-access-log';
    }

    public function getRemotePath()
    {
        return 'logs/access_log';
    }

    public function getWatchByDefault()
    {
        return LogInterface::DONT_WATCH_BY_DEFAULT;
    }
}
