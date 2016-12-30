<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Environment;
use DeltaCli\Log\LogInterface;

class VagrantApacheErrorLog extends AbstractRemoteFile
{
    public function getName()
    {
        return 'vagrant-apache-error-log';
    }

    public function getRemotePath(Environment $environment)
    {
        return '/var/log/httpd/error_log';
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
        return true;
    }
}
