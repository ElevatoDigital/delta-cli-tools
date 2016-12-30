<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Environment;
use DeltaCli\Log\LogInterface;

class VagrantNginxAccessLog extends AbstractRemoteFile
{
    public function getName()
    {
        return 'vagrant-nginx-access-log';
    }

    public function getRemotePath(Environment $environment)
    {
        return '/var/log/nginx/access.log';
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
