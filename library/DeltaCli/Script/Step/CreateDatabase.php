<?php

namespace DeltaCli\Script\Step;

use DeltaCli\ApiClient;
use DeltaCli\Environment;
use DeltaCli\Environment\ApiEnvironment;
use DeltaCli\Exception\DeltaApiEnvironmentRequired;
use DeltaCli\Project;

class CreateDatabase extends DeltaApiAbstract implements EnvironmentAwareInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var ApiEnvironment
     */
    private $environment;

    public function __construct(Project $project, $type, ApiClient $apiClient = null)
    {
        parent::__construct($project, $apiClient);
        $this->type = $type;
    }

    public function setSelectedEnvironment(Environment $environment)
    {
        if (!$environment instanceof ApiEnvironment) {
            throw new DeltaApiEnvironmentRequired('Can only create databases for environments created with Delta API.');
        }

        $this->environment = $environment;

        return $this;
    }

    public function run()
    {
        $response = $this->apiClient->createDatabase(
            $this->project->getSlug(),
            $this->environment->getName(),
            $this->type
        );

        if (200 !== $response->getStatusCode()) {
            $this->apiClient->handleUnsuccessfulResponse($response, $this->output);

            return new Result($this, Result::FAILURE);
        } else {
            $responseData = json_decode($response->getBody()->getContents(), true);

            $this->environment->applyResponseData($responseData['environment']);

            return new Result($this, Result::SUCCESS);
        }
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        } else {
            return 'create-database';
        }
    }
}