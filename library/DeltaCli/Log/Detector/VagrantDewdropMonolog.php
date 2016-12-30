<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Environment;
use DeltaCli\Log\LogInterface;

class VagrantDewdropMonolog extends AbstractRemoteFile
{
    public function getName()
    {
        return 'vagrant-dewdrop-monolog';
    }

    public function getRemotePath(Environment $environment)
    {
        return "logs/{$environment->getApplicationEnv()}.log";
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
