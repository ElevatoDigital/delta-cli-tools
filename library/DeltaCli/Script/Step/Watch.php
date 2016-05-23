<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;
use DeltaCli\Exception\NoOtherStepsCanBeAddedAfterWatch;
use DeltaCli\FileWatcher\FileWatcherInterface;
use DeltaCli\Script as ScriptObject;

class Watch extends StepAbstract implements EnvironmentOptionalInterface
{
    /**
     * @var ScriptObject
     */
    private $script;

    /**
     * @var array
     */
    private $paths = [];

    /**
     * @var bool
     */
    private $stopOnFailure = false;

    /**
     * @var bool
     */
    private $onlyNotifyOnFailure = false;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var FileWatcherInterface
     */
    private $fileWatcher;

    public function __construct(ScriptObject $script, FileWatcherInterface $fileWatcher)
    {
        $this->script      = $script;
        $this->fileWatcher = $fileWatcher;
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        } else {
            return 'watch';
        }
    }

    public function setSelectedEnvironment(Environment $environment)
    {
        $this->environment = $environment;

        return $this;
    }

    public function addPaths(array $paths)
    {
        foreach ($paths as $path) {
            $this->addPath($path);
        }

        return $this;
    }

    public function addPath($path)
    {
        $this->paths[] = $path;

        return $this;
    }

    public function setStopOnFailure($stopOnFailure)
    {
        $this->stopOnFailure = $stopOnFailure;

        return $this;
    }

    public function setOnlyNotifyOnFailure($onlyNotifyOnFailure)
    {
        $this->onlyNotifyOnFailure = $onlyNotifyOnFailure;

        return $this;
    }

    public function run()
    {
        if (count($this->paths)) {
            if ($this->environment) {
                $this->script->setEnvironment($this->environment);
            }
            
            $this->fileWatcher->addWatch($this->paths, $this->script, $this->onlyNotifyOnFailure, $this->stopOnFailure);
            return new Result($this, Result::SUCCESS);
        } else {
            $result = new Result($this, Result::FAILURE);
            $result->setExplanation('because no paths were added to watch');
            return $result;
        }
    }

    public function postRun(ScriptObject $script)
    {
        $this->fileWatcher->startLoop();
    }

    public function addStepToScript(ScriptObject $script, StepInterface $step)
    {
        if (!$step instanceof Watch) {
            throw new NoOtherStepsCanBeAddedAfterWatch();
        }
    }
}
