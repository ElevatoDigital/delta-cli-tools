<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Cache;
use DeltaCli\Environment;
use DeltaCli\Exception\AttemptedPotentiallyDestructiveOperation;
use Symfony\Component\Console\Input\InputInterface;

class SanityCheckPotentiallyDangerousOperation extends StepAbstract implements EnvironmentAwareInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var bool
     */
    private $skipOnDevEnvironments = true;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $operationDescription;

    /**
     * @var string
     */
    private $cacheKey = 'potentially-destructive-operation-authorization-code';

    public function __construct(InputInterface $input, Cache $cache, $operationDescription)
    {
        $this->input                = $input;
        $this->cache                = $cache;
        $this->operationDescription = $operationDescription;
    }

    public function setSkipOnDevEnvironments($skipOnDevEnvironments)
    {
        $this->skipOnDevEnvironments = $skipOnDevEnvironments;

        return $this;
    }

    public function run()
    {
        if ($this->environment->isDevEnvironment() && $this->skipOnDevEnvironments) {
            $result = new Result($this, Result::SKIPPED);
            $result->setExplanation("because {$this->environment->getName()} is a dev environment");
            return $result;
        }

        if (!$this->isAuthorized()) {
            $exception = new AttemptedPotentiallyDestructiveOperation();
            $exception
                ->setEnvironment($this->environment)
                ->setAuthorizationCode($this->generateAuthorizationCode())
                ->setOperationDescription($this->operationDescription);
            throw $exception;
        }

        $this->cache->clear($this->cacheKey);

        return new Result($this, Result::SUCCESS);
    }

    public function getName()
    {
        return 'sanity-check-potentially-dangerous-operation';
    }

    public function setSelectedEnvironment(Environment $environment)
    {
        $this->environment = $environment;

        return $this;
    }

    private function isAuthorized()
    {
        if (!$this->cache->fetch($this->cacheKey)) {
            return false;
        }

        if (!$this->input->hasOption('authorization-code')) {
            return false;
        }

        if ($this->input->getOption('authorization-code') !== $this->cache->fetch($this->cacheKey)) {
            return false;
        }

        return true;
    }

    private function generateAuthorizationCode()
    {
        $authorizationCode = bin2hex(random_bytes(12));

        $this->cache->store($this->cacheKey, $authorizationCode);

        return $authorizationCode;
    }
}
