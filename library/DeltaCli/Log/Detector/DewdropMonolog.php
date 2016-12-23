<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Environment;
use DeltaCli\Log\LogInterface;

class DewdropMonolog extends AbstractRemoteFile
{
    public function getName()
    {
        return 'dewdrop-monolog';
    }

    public function getRemotePath(Environment $environment)
    {
        return "zend/logs/{$environment->getApplicationEnv()}";
    }

    public function getWatchByDefault()
    {
        return LogInterface::WATCH_BY_DEFAULT;
    }
}
