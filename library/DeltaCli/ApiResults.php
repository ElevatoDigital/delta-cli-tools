<?php

namespace DeltaCli;

use DeltaCli\Script\Step\Result;

class ApiResults
{
    /**
     * @var Script
     */
    private $script;

    /**
     * @var array
     */
    private $stepResults = [];

    /**
     * @var string
     */
    private $scriptResult;

    /**
     * ApiResults constructor.
     * @param Script $script
     */
    public function __construct(Script $script)
    {
        $this->script = $script;
    }

    public function setScriptResult($scriptResult)
    {
        $this->scriptResult = $scriptResult;

        return $this;
    }

    public function getScriptResult()
    {
        return $this->scriptResult;
    }

    public function addStepResult(Result $stepResult)
    {
        $this->stepResults[] = $stepResult;

        return $this;
    }

    public function toJson()
    {
        $environmentName = null;

        if ($this->script->getEnvironment()) {
            $environmentName = $this->script->getEnvironment()->getName();
        }

        return json_encode(
            [
                'script'      => $this->script->getName(),
                'environment' => $environmentName,
                'result'      => $this->scriptResult,
                'steps'       => $this->getStepResults(),
                'attributes'  => []
            ]
        );
    }

    public function getStepResults()
    {
        $results = [];

        /* @var $result Result */
        foreach ($this->stepResults as $result) {
            $results[] = [
                'name'           => $result->getStepName(),
                'status'         => $result->getStatus(),
                'status_message' => $result->getMessageText(),
                'output_type'    => 'text',
                'output'         => $result->getApiOutput()
            ];
        }

        return $results;
    }
}
