<?php

namespace DeltaCli\Environment\Provider;

class Vagrant implements ProviderInterface
{
    public function getName()
    {
        return 'vagrant';
    }

    public function requiresEnvironmentName()
    {
        return false;
    }
}