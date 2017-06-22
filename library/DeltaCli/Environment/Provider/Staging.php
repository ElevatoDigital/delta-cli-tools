<?php

namespace DeltaCli\Environment\Provider;

class Staging implements ProviderInterface
{
    public function getName()
    {
        return 'staging';
    }

    public function requiresEnvironmentName()
    {
        return true;
    }
}