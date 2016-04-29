<?php

namespace DeltaCli;

use DeltaCli\Exception\EnvironmentNotFound;
use DeltaCli\Exception\ProjectNotConfigured;
use DeltaCli\Exception\ScriptNotFound;

class Project
{
    /**
     * @var string
     */
    private $name = 'Delta Systems CLI Tools';

    /**
     * @var array
     */
    private $environments = [];

    /**
     * @var array
     */
    private $scripts = [];

    public function __construct()
    {
        $this
            ->createScript('deploy', 'Deploy this project.')
            ->createScript('create-environment', 'Create databases and other resources needed for a new environment.');
    }

    public static function fromConfigFile()
    {
        $cwd = getcwd();

        if (!file_exists($cwd . '/delta-cli.php')) {
            throw new ProjectNotConfigured();
        }

        $project = new Project();
        require_once $cwd . '/delta-cli.php';
        return $project;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getScripts()
    {
        return $this->scripts;
    }

    /**
     * @param $name
     * @return Script
     * @throws ScriptNotFound
     */
    public function getScript($name)
    {
        if (!array_key_exists($name, $this->scripts)) {
            throw new ScriptNotFound("No script could be found with the name '{$name}'.");
        }

        return $this->scripts[$name];
    }

    public function addScript(Script $script)
    {
        $this->scripts[$script->getName()] = $script;
        return $this;
    }

    public function createScript($name, $description)
    {
        return $this->addScript(new Script($this, $name, $description));
    }

    /**
     * @return Script
     * @throws ScriptNotFound
     */
    public function getDeployScript()
    {
        return $this->getScript('deploy');
    }

    public function createEnvironment($name)
    {
        $this->environments[$name] = new Environment($name);
        return $this;
    }

    public function addEnvironment(Environment $environment)
    {
        $this->environments[$environment->getName()] = $environment;
        return $this;
    }

    public function hasEnvironment($name)
    {
        return array_key_exists($name, $this->environments);
    }

    public function getEnvironment($name)
    {
        if (!$this->hasEnvironment($name)) {
            $exception = new EnvironmentNotFound();
            $exception->setName($name);
            throw $exception;
        }

        return $this->environments[$name];
    }
}
