<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Environment;
use DeltaCli\Log\LogInterface;

class NginxErrorLog extends AbstractRemoteFile
{
    public function getName()
    {
        return 'nginx-error-log';
    }

    public function getRemotePath(Environment $environment)
    {
        return 'logs/error.nginx.log';
    }

    public function getWatchByDefault()
    {
        return LogInterface::DONT_WATCH_BY_DEFAULT;
    }
}
