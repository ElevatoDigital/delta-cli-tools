<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;

interface StepInterface
{
    public function setEnvironments(array $environments);

    public function run();

    public function getName();

    public function appliesToEnvironment(Environment $environment);
}
