<?php

namespace DeltaCli\Script\Step;

use Symfony\Component\Console\Output\OutputInterface;

class PhpCallableSupportingDryRun extends PhpCallable implements DryRunInterface
{
    /**
     * @var callable
     */
    private $dryRunCallable;

    public function __construct(callable $callable, callable $dryRunCallable, OutputInterface $output)
    {
        parent::__construct($callable, $output);

        $this->dryRunCallable = $dryRunCallable;
    }

    public function dryRun()
    {
        return $this->runCallable($this->dryRunCallable);
    }
}
