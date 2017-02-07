<?php

namespace DeltaCli\Environment;

use DeltaCli\Config\Config;
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

    private function getResponseDataField(array $resources, $id)
    {
        if (!$this->privateKey) {
            $this->privateKey = openssl_pkey_get_private($this->apiPrivateKeyString);
        }

        foreach ($resources as $resource) {
            if ($resource['name'] === $id) {
                openssl_open(
                    hex2bin($resource['encrypted_contents']),
                    $plainText,
                    hex2bin($resource['encrypted_key']),
                    $this->privateKey
                );

                return json_decode($plainText, true);
            }
        }

        throw new DeltaApiResourceNotFound("Could not find a resource with the name '{$id}'.");
    }
}