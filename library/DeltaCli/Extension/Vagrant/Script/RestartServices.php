<?php

namespace DeltaCli\Extension\Vagrant\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use Exception;

class RestartServices extends Script
{
    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'vagrant:restart-services',
            'Restart HTTP and database services.'
        );
    }

    protected function addSteps()
    {
        $this
            ->setStopOnFailure(false)
            ->addStep($this->getProject()->getScript('vagrant:check-environment'))
            ->addStep('restart-apache', $this->getProject()->ssh('sudo service httpd restart'))
            ->addStep('restart-nginx', $this->getProject()->ssh('sudo service nginx restart'))
            ->addStep(
                'restart-postgres',
                $this->attemptRestartUsingCandidateServiceNames(
                    'postgres',
                    [
                        'postgresql-9.6',
                        'postgresql-9.5',
                        'postgresql-9.4',
                        'postgresql-9.3',
                        'postgresql-9.2',
                        'postgresql',
                        'postgres'
                    ]
                )
            )
            ->addStep(
                'restart-mysql',
                $this->attemptRestartUsingCandidateServiceNames('mysql', ['mariadb', 'mysql'])
            );
    }

    /**
     * For Postgres and MySQL/MariaDB, there are a number of differen potential service names.
     * We try each possible service name and return a successful result if at least one successfully
     * restarts.
     *
     * Note: This method in particular returns a callable that can be used as a Delta CLI step.
     *
     * @param string $service The service you're attempting to restart.
     * @param string[] $candidateServiceNames
     * @return callable
     */
    protected function attemptRestartUsingCandidateServiceNames($service, $candidateServiceNames)
    {
        return function () use ($candidateServiceNames, $service) {
            foreach ($candidateServiceNames as $serviceName) {
                $step = $this->getProject()->ssh(
                    sprintf('sudo service %s restart', escapeshellarg($serviceName))
                );

                $step->setSelectedEnvironment($this->getProject()->getEnvironment('vagrant'));

                $result = $step->run();

                if ($result->isSuccess()) {
                    return true;
                }
            }

            throw new Exception("Failed to restart {$service}.");
        };
    }

    protected function preRun()
    {
        $this->setEnvironment('vagrant');
    }
}
