<?php

namespace DeltaCli\Environment\Provider;

class ProviderSet
{
    /**
     * @var ProviderInterface[]
     */
    private $providers = [];

    public function __construct()
    {
        $this->providers[] = new Dev();
    }

    public function getAll()
    {
        return $this->providers;
    }

    public function get($name)
    {
        foreach ($this->providers as $provider) {
            if ($provider->getName() === $name) {
                return $provider;
            }
        }

        return false;
    }
}