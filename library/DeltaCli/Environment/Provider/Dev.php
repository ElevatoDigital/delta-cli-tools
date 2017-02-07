<?php

namespace DeltaCli\Environment\Provider;

class Dev implements ProviderInterface
{
    public function getName()
    {
        return 'dev';
    }

    public function requiresEnvironmentName()
    {
        return true;
    }
}