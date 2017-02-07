<?php

namespace DeltaCli\Environment\Provider;

interface ProviderInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return bool
     */
    public function requiresEnvironmentName();
}