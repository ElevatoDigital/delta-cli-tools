<?php

namespace DeltaCli\Script\Step;

use Closure;
use Exception;

class PhpCallable extends StepAbstract
{
    /**
     * @var callable
     */
    protected $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function run()
    {
        return $this->runCallable($this->callable);
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        } else if (is_array($this->callable)) {
            return $this->generateNameForArrayCallable($this->callable);
        } else if (is_string($this->callable)) {
            return $this->callable;
        } else if ($this->callable instanceof Closure) {
            return 'PHP Closure';
        } else {
            return 'PHP Callback';
        }
    }

    protected function runCallable(callable $callable)
    {
        try {
            ob_start();
            $result = call_user_func($callable);
            $output = ob_get_clean();

            if (!$result instanceof Result) {
                $result = new Result($this, Result::SUCCESS, $output);
            }
        } catch (Exception $e) {
            // Close output buffer left open due to exception being thrown
            ob_get_clean();

            $exceptionClass = get_class($e);

            $result = new Result(
                $this,
                Result::FAILURE,
                [
                    "An uncaught {$exceptionClass} was thrown.",
                    $e->getMessage()
                ]
            );
        }

        return $result;
    }

    private function generateNameForArrayCallable(array $callable)
    {
        if (is_string($callable[0])) {
            return sprintf('%s::%s()', $callable[0], $callable[1]);
        } else {
            return sprintf('%s->%s()', get_class($callable[0]), $callable[1]);
        }
    }
}
