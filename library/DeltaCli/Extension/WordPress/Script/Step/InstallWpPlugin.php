<?php

namespace DeltaCli\Extension\WordPress\Script\Step;

use DeltaCli\Host;
use DeltaCli\Script\Step\EnvironmentHostsStepAbstract;
use DeltaCli\Script\Step\Result;
use DeltaCli\Extension\WordPress\Exception\WpApiException;

class InstallWpPlugin extends EnvironmentHostsStepAbstract
{
    /**
     * @var string
     */
    private $slug;

    public function __construct($slug)
    {
        $this->slug = $slug;
    }

    public function runOnHost(Host $host)
    {
        try {
            $downloadUrl = $this->getDownloadUrl();
        } catch (WpApiException $e) {
            return new Result($this, Result::FAILURE, $e->getMessage());
        }


    }

    public function getName()
    {
        return 'install-' . $this->filterSlug($this->slug) . '-wp-plugin';
    }

    private function filterSlug($slug)
    {
        return strtolower(str_replace([' ', '_'], '-', trim($slug)));
    }

    private function getDownloadUrl()
    {
        $apiUrl = sprintf('https://api.wordpress.org/plugins/info/1.0/%s.json', $this->slug);
        $json   = file_get_contents($apiUrl);
        $data   = @json_decode($json, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new WpApiException('Error parsing WP API JSON: ' . json_last_error_msg());
        }

        if (!isset($data['download_link']) || !$data['download_link']) {
            throw new WpApiException('download_link not present in WP API JSON.');
        }

        return $data['download_link'];
    }

}
