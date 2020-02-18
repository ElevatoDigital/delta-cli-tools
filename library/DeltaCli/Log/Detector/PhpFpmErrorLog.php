<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Environment;
use DeltaCli\Log\LogInterface;

class PhpFpmErrorLog extends AbstractRemoteFile
{
    public function getName()
    {
        return 'php-fpm-error-log';
    }

    public function getRemotePath(Environment $environment)
    {
        return 'logs/php_fpm_error_log';
    }

    public function getWatchByDefault()
    {
        return LogInterface::WATCH_BY_DEFAULT;
    }
}
