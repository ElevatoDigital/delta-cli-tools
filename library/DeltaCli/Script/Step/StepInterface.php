<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;

interface StepInterface
{
    public function setEnvironments(array $environments);

    /**
     * @return Result
     */
    public function run();

    public function getName();

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name);

    public function appliesToEnvironment(Environment $environment);
}
