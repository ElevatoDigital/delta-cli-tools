<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Environment;
use DeltaCli\Log\LogInterface;

class VagrantNginxErrorLog extends AbstractRemoteFile
{
    public function getName()
    {
        return 'vagrant-nginx-error-log';
    }

    public function getRemotePath(Environment $environment)
    {
        return '/var/log/nginx/error.log';
    }

    public function getWatchByDefault()
    {
        return LogInterface::DONT_WATCH_BY_DEFAULT;
    }

    protected function detectorAppliesToEnvironment(Environment $environment)
    {
        return $environment->getName() === 'vagrant';
    }

    protected function requiresRoot()
    {
        return true;
    }
}
