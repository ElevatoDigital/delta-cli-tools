<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Environment;
use DeltaCli\Log\LogInterface;

class VagrantApacheAccessLog extends AbstractRemoteFile
{
    public function getName()
    {
        return 'vagrant-apache-access-log';
    }

    public function getRemotePath(Environment $environment)
    {
        return '/var/log/httpd/access_log';
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
