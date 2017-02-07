<?php

namespace DeltaCli\Script;

use DeltaCli\Environment\Provider\ProviderSet;
use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Input\InputArgument;

class EnvironmentCreate extends Script
{
    /**
     * @var string
     */
    private $provider;

    /**
     * @var string
     */
    private $environmentName;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'env:create',
            'Create a new environment.'
        );
    }

    protected function configure()
    {
        $this->addSetterArgument(
            'provider',
            InputArgument::REQUIRED,
            'The environment provider.  Probably dev.  Only dev is supported currently.'
        );

        $this->addSetterArgument(
            'environment-name',
            InputArgument::REQUIRED,
            'The environment name.'
        );

        parent::configure();
    }

    public function setProvider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    public function setEnvironmentName($environmentName)
    {
        $this->environmentName = $environmentName;

        return $this;
    }

    protected function addSteps()
    {
        $providerSet = new ProviderSet();
        $provider    = $providerSet->get($this->provider);

        $createStep = $this->getProject()->createEnvironmentStep($provider, $this->environmentName);

        $this
            ->addStep($createStep)
            ->addStep(
                'install-ssh-key',
                function () use ($createStep) {
                    /* @var $script \DeltaCli\Script\SshInstallKey */
                    $script = $this->getProject()->getScript('ssh:install-key');

                    $script
                        ->setEnvironment($createStep->getEnvironment())
                        ->setPassword($createStep->getEnvironment()->getPassword());

                    return $this->getProject()->scriptStep($script, true)->run();
                }
            )
            ->addStep(
                'display-resources',
                function () use ($createStep) {
                    return $this->getProject()->displayEnvironmentResources()
                        ->setSelectedEnvironment($createStep->getEnvironment())
                        ->run();
                }
            );
    }
}