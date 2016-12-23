<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Environment;
use DeltaCli\Log\LogInterface;

class NginxAccessLog extends AbstractRemoteFile
{
    public function getName()
    {
        return 'nginx-access-log';
    }

    public function getRemotePath(Environment $environment)
    {
        return 'logs/access.nginx.log';
    }

    public function getWatchByDefault()
    {
        return LogInterface::DONT_WATCH_BY_DEFAULT;
    }
}
