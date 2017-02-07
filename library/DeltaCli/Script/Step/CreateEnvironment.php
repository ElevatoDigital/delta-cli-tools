<?php

namespace DeltaCli\Script\Step;

use DeltaCli\ApiClient;
use DeltaCli\Environment\ApiEnvironment;
use DeltaCli\Environment\Provider\ProviderInterface;
use DeltaCli\Project;

class CreateEnvironment extends DeltaApiAbstract
{
    /**
     * @var ProviderInterface
     */
    private $provider;

    /**
     * @var string
     */
    private $environmentName;

    /**
     * @var ApiEnvironment
     */
    private $environment;

    public function __construct(
        Project $project,
        ProviderInterface $provider,
        $environmentName,
        ApiClient $apiClient = null
    )
    {
        $this->provider        = $provider;
        $this->environmentName = $environmentName;
        parent::__construct($project, $apiClient);
    }

    public function run()
    {
        if ($this->project->hasEnvironment($this->environmentName)) {
            return new Result(
                $this,
                Result::FAILURE,
                ["An environment with the name {$this->environmentName} already exists."]
            );
        }

        if ($this->apiClient->hasResourcePublicKey()) {
            $publicKey = $this->apiClient->getResourcePublicKey();
        } else {
            $publicKey = $this->convertPublicKey();

            $this->apiClient->writeProjectKey(
                $this->apiClient->getProjectKey(),
                $this->convertPrivateKey(),
                $publicKey
            );
        }

        $response = $this->apiClient->createEnvironment(
            $this->project->getSlug(),
            $this->provider->getName(),
            $this->environmentName,
            $publicKey
        );

        if (200 !== $response->getStatusCode()) {
            $this->apiClient->handleUnsuccessfulResponse($response, $this->output);

            return new Result($this, Result::FAILURE);
        } else {
            $this->environment = new ApiEnvironment(
                $this->project,
                $this->environmentName,
                $this->apiClient->getResourcePrivateKey()
            );

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
            return 'create-environment';
        }
    }

    private function convertPrivateKey()
    {
        $privateKey = openssl_pkey_get_private(file_get_contents(getcwd() . '/ssh-keys/id_rsa'));

        openssl_pkey_export($privateKey, $output);

        return $output;
    }

    private function convertPublicKey()
    {
        $publicKey = shell_exec(
            sprintf(
                'ssh-keygen -f %s -e -m PKCS8',
                escapeshellarg(getcwd() . '/ssh-keys/id_rsa.pub')
            )
        );

        return $publicKey;
    }
}