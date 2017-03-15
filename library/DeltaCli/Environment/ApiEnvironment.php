<?php

namespace DeltaCli\Environment;

use DeltaCli\Config\Config;
use DeltaCli\Config\Database\DatabaseFactory;
use DeltaCli\Environment;
use DeltaCli\Exception\DeltaApiResourceNotFound;
use DeltaCli\Project;

class ApiEnvironment extends Environment
{
    /**
     * @var Config
     */
    private $apiConfig;

    /**
     * @var resource
     */
    private $privateKey;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $apiPrivateKeyString;

    public function __construct(Project $project, $name, $apiPrivateKeyString)
    {
        parent::__construct($project, $name);

        $this->apiConfig           = new Config();
        $this->apiPrivateKeyString = $apiPrivateKeyString;
    }

    public function getApiConfig()
    {
        return $this->apiConfig;
    }

    public function applyResponseData(array $responseData)
    {
        $resources = $responseData['resources'];

        $this
            ->setIsDevEnvironment($this->getResponseDataField($resources, 'dev-environment-flag'))
            ->setUsername($this->getResponseDataField($resources, 'sftp-username'))
            ->setPassword($this->getResponseDataField($resources, 'sftp-password'))
            ->setSshPrivateKey(getcwd() . '/ssh-keys/id_rsa')
            ->addHost($this->getResponseDataField($resources, 'host'));

        $this->getApiConfig()->setBrowserUrl($this->getResponseDataField($resources, 'browser-url'));

        foreach ($this->getDatabaseResources($resources) as $database) {
            $this->getApiConfig()->addDatabase(
                DatabaseFactory::createInstance(
                    $database['type'],
                    $database['name'],
                    $database['username'],
                    $database['password'],
                    $database['host']
                )
            );
        }

        return $this;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }

    private function getDatabaseResources(array $resources)
    {
        if (!$this->privateKey) {
            $this->privateKey = openssl_pkey_get_private($this->apiPrivateKeyString);
        }
        
        $databases = [];

        foreach ($resources as $resourceData) {
            if ('database' === $resourceData['name']) {
                $databases[] = $this->decryptResourceData($resourceData);
            }
        }

        return $databases;
    }

    private function getResponseDataField(array $resources, $id)
    {
        if (!$this->privateKey) {
            $this->privateKey = openssl_pkey_get_private($this->apiPrivateKeyString);
        }

        foreach ($resources as $resource) {
            if ($resource['name'] === $id) {
                return $this->decryptResourceData($resource);
            }
        }

        throw new DeltaApiResourceNotFound("Could not find a resource with the name '{$id}'.");
    }

    private function decryptResourceData(array $resourceData)
    {
        openssl_open(
            hex2bin($resourceData['encrypted_contents']),
            $plainText,
            hex2bin($resourceData['encrypted_key']),
            $this->privateKey
        );

        return json_decode($plainText, true);
    }
}