<?php

namespace DeltaCli\Script\Step;

interface DryRunInterface
{
    /**
     * @return Result
     */
    public function dryRun();
}
