<?php

namespace DeltaCli;

use GuzzleHttp\Client as GuzzleClient;

class ApiClient
{
    const BASE_URL = 'https://deploy.deltasys.com/';

    const VERSION = 'v1';

    /**
     * @var GuzzleClient
     */
    private $guzzleClient;

    /**
     * @var string
     */
    private $homeFolder;

    public function __construct(GuzzleClient $guzzleClient = null)
    {
        $this->guzzleClient = ($guzzleClient ?: new GuzzleClient(['exceptions' => false]));
        $this->homeFolder   = $_SERVER['HOME'];
    }

    public function setHomeFolder($homeFolder)
    {
        $this->homeFolder = $homeFolder;

        return $this;
    }

    public function hasAccountKey()
    {
        return $this->hasKey($this->getAccountKeyJsonPath());
    }

    public function writeAccountKey($apiKey)
    {
        file_put_contents(
            $this->getAccountKeyJsonPath(),
            json_encode(['apiKey' => $apiKey]),
            LOCK_EX
        );

        return $this;
    }

    public function getAccountKeyJsonPath()
    {
        return $this->homeFolder . '/.delta-api.json';
    }

    public function hasProjectKey()
    {
        return $this->hasKey($this->getProjectKeyJsonPath());
    }

    public function writeProjectKey($apiKey)
    {
        file_put_contents(
            $this->getProjectKeyJsonPath(),
            json_encode(['apiKey' => $apiKey]),
            LOCK_EX
        );

        return $this;
    }

    public function getProjectKeyJsonPath()
    {
        return getcwd() . '/.delta-api.json';
    }

    public function signUpWithEmail($emailAddress)
    {
        return $this->guzzleClient->request(
            'POST',
            $this->url('/sign-up-with-email'),
            [
                'form_params' => [
                    'email_address' => $emailAddress
                ]
            ]
        );
    }

    public function login($emailAddress, $password)
    {
        return $this->guzzleClient->request(
            'POST',
            $this->url('/login'),
            [
                'form_params' => [
                    'email_address' => $emailAddress,
                    'password'      => $password
                ]
            ]
        );
    }

    public function createAccount($authorizationCode, $password, $confirmPassword)
    {
        return $this->guzzleClient->request(
            'PUT',
            $this->url('/account'),
            [
                'form_params' => [
                    'authorization_code' => $authorizationCode,
                    'password'           => $password,
                    'confirm_password'   => $confirmPassword
                ]
            ]
        );
    }

    private function hasKey($jsonFile)
    {
        if (!file_exists($jsonFile) || !is_readable($jsonFile)) {
            return false;
        }

        $content = file_get_contents($jsonFile);
        $data    = json_decode($content, true);

        return isset($data['apiKey']) && $data['apiKey'];
    }

    private function url($url)
    {
        return self::BASE_URL . self::VERSION . '/' . ltrim($url, '/');
    }
}
