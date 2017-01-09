<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Environment;
use DeltaCli\Log\LogInterface;

class VagrantPhpErrorLog extends AbstractRemoteFile
{
    public function getName()
    {
        return 'vagrant-php-error-log';
    }

    public function getRemotePath(Environment $environment)
    {
        return '/delta/php_errors';
    }

    public function getWatchByDefault()
    {
        return LogInterface::WATCH_BY_DEFAULT;
    }

    protected function detectorAppliesToEnvironment(Environment $environment)
    {
        return $environment->getName() === 'vagrant';
    }

    protected function requiresRoot()
    {
        return false;
    }
}
