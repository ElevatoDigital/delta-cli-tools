<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;

interface EnvironmentOptionalInterface
{
    public function setSelectedEnvironment(Environment $environment);
}
