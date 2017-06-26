<?php

namespace DeltaCli;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Output\OutputInterface;

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
        $stack = new HandlerStack();

        if (isset($_SERVER['USER']) && 'vagrant' === $_SERVER['USER']) {
            $stack->setHandler(new StreamHandler());
        } else {
            $stack->setHandler(\GuzzleHttp\choose_handler());
        }

        $this->guzzleClient = ($guzzleClient ?: new GuzzleClient(['exceptions' => false, 'handler' => $stack]));
        //$this->homeFolder   = $_SERVER['HOME'];
        $this->homeFolder     = '~';
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

    public function hasResourcePrivateKey()
    {
        return $this->hasKey($this->getProjectKeyJsonPath(), 'resourcePrivateKey');
    }

    public function getResourcePrivateKey()
    {
        if (!$this->hasResourcePrivateKey()) {
            return '';
        } else {
            $contents = file_get_contents($this->getProjectKeyJsonPath());
            $json     = json_decode($contents, true);
            return $json['resourcePrivateKey'];
        }
    }

    public function hasResourcePublicKey()
    {
        return $this->hasKey($this->getProjectKeyJsonPath(), 'resourcePublicKey');
    }

    public function getResourcePublicKey()
    {
        if (!$this->hasResourcePublicKey()) {
            return '';
        } else {
            $contents = file_get_contents($this->getProjectKeyJsonPath());
            $json     = json_decode($contents, true);
            return $json['resourcePublicKey'];
        }
    }

    public function writeProjectKey($apiKey, $resourcePrivateKey = null, $resourcePublicKey = null)
    {
        file_put_contents(
            $this->getProjectKeyJsonPath(),
            json_encode(
                [
                    'apiKey'             => $apiKey,
                    'resourcePrivateKey' => $resourcePrivateKey,
                    'resourcePublicKey'  => $resourcePublicKey
                ]
            ),
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

    public function createEnvironment($slug, $providerName, $environmentName, $publicKey)
    {
        return $this->guzzleClient->request(
            'POST',
            $this->url("/project/{$this->getProjectKey()}/environment"),
            [
                'auth'        => [$this->getAccountKey(), $this->getAccountKey()],
                'form_params' => [
                    'slug'        => $slug,
                    'provider'    => $providerName,
                    'environment' => $environmentName,
                    'public_key'  => $publicKey
                ]
            ]
        );
    }

    public function createDatabase($slug, $environmentName, $type)
    {
        return $this->guzzleClient->request(
            'POST',
            $this->url("/project/{$this->getProjectKey()}/environment/{$environmentName}/database"),
            [
                'auth'        => [$this->getAccountKey(), $this->getAccountKey()],
                'form_params' => [
                    'slug' => $slug,
                    'type' => $type
                ]
            ]
        );
    }

    public function getEnvironment($environmentName)
    {
        return $this->guzzleClient->request(
            'GET',
            $this->url("/project/{$this->getProjectKey()}/environment/{$environmentName}"),
            [
                'auth' => [$this->getAccountKey(), $this->getAccountKey()]
            ]
        );
    }

    public function listEnvironments()
    {
        return $this->guzzleClient->request(
            'GET',
            $this->url("/project/{$this->getProjectKey()}/environments"),
            [
                'auth' => [$this->getAccountKey(), $this->getAccountKey()]
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

    public function handleUnsuccessfulResponse(Response $response, OutputInterface $output)
    {
        if ('application/json' !== $response->getHeaderLine('Content-Type')) {
            $output->writeln("<error>Invalid API response returned: {$response->getReasonPhrase()}.</error>");
        } else {
            $responseDetails = json_decode($response->getBody(), true);

            if (isset($responseDetails['message']) && isset($responseDetails['code'])) {
                $output->writeln("<error>{$responseDetails['message']} ({$responseDetails['code']})</error>");
            } else {
                $output->writeln("<error>Delta API response didn't have the expected message and code fields.</error>");
            }
        }

        $output->writeln('');
    }

    private function hasKey($jsonFile, $keyIndex = 'apiKey')
    {
        if (!file_exists($jsonFile) || !is_readable($jsonFile)) {
            return false;
        }

        $content = file_get_contents($jsonFile);
        $data    = json_decode($content, true);

        return isset($data[$keyIndex]) && $data[$keyIndex];
    }

    private function url($url)
    {
        if (isset($_SERVER['DELTA_API_TEST']) && $_SERVER['DELTA_API_TEST']) {
            $baseUrl = 'http://delta-api.local:8080/';
        } else {
            $baseUrl = self::BASE_URL;
        }

        return $baseUrl . self::VERSION . '/' . ltrim($url, '/');
    }
}
