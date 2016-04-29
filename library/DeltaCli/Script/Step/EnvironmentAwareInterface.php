<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;

interface EnvironmentAwareInterface
{
    public function setSelectedEnvironment(Environment $environment);
}
