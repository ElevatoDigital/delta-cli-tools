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

    public function getAccountKey()
    {
        if (!$this->hasAccountKey()) {
            return '';
        } else {
            $contents = file_get_contents($this->getAccountKeyJsonPath());
            $json     = json_decode($contents, true);
            return $json['apiKey'];
        }
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

    public function getProjectKey()
    {
        if (!$this->hasProjectKey()) {
            return '';
        } else {
            $contents = file_get_contents($this->getProjectKeyJsonPath());
            $json     = json_decode($contents, true);
            return $json['apiKey'];
        }
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
        return getcwd() . '/delta-api.json';
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
            'POST',
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

    public function createProject($name)
    {
        return $this->guzzleClient->request(
            'POST',
            $this->url('/project'),
            [
                'auth'        => [$this->getAccountKey(), $this->getAccountKey()],
                'form_params' => ['name' => $name]
            ]
        );
    }

    public function getProject($apiKey)
    {
        return $this->guzzleClient->request(
            'GET',
            $this->url("/project/{$apiKey}"),
            [
                'auth' => [$this->getAccountKey(), $this->getAccountKey()],
            ]
        );
    }

    public function postResults(ApiResults $apiResults, Project $project, $sendNotifications)
    {
        return $this->guzzleClient->request(
            'POST',
            $this->url('/results'),
            [
                'auth' => [$this->getAccountKey(), $this->getAccountKey()],
                'form_params' => [
                    'project'            => $this->getProjectKey(),
                    'results'            => $apiResults->toJson(),
                    'slack_channel'      => $project->getSlackChannel(),
                    'slack_handles'      => json_encode($project->getSlackHandles()),
                    'send_notifications' => (int) $sendNotifications
                ]
            ]
        );
    }

    public function fetchLog($script, $environment)
    {
        return $this->guzzleClient->request(
            'GET',
            $this->url("/project/{$this->getProjectKey()}/log"),
            [
                'auth' => [$this->getAccountKey(), $this->getAccountKey()],
                'query' => [
                    'script'      => $script,
                    'environment' => $environment
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
