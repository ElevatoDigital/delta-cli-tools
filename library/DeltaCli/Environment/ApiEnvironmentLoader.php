<?php

namespace DeltaCli\Environment;

use DeltaCli\ApiClient;
use DeltaCli\Project;

class ApiEnvironmentLoader
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @var ApiClient
     */
    private $apiClient;

    public function __construct(Project $project, ApiClient $apiClient = null)
    {
        $this->project   = $project;
        $this->apiClient = ($apiClient ?: new ApiClient());
    }

    public function load()
    {
        if (!$this->apiClient->hasAccountKey() || !$this->apiClient->hasProjectKey()) {
            return;
        }

        $response = $this->apiClient->listEnvironments();

        if (200 !== $response->getStatusCode()) {
            $this->apiClient->handleUnsuccessfulResponse($response, $this->project->getOutput());
        } else {
            $responseData = json_decode($response->getBody(), true);

            foreach ($responseData['environments'] as $environmentData) {
                $environment = new ApiEnvironment(
                    $this->project,
                    $environmentData['name'],
                    $this->apiClient->getResourcePrivateKey()
                );
                $environment->applyResponseData($environmentData);
                $this->project->addEnvironment($environment);
            }
        }
    }
}