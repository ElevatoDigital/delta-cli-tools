<?php

namespace DeltaCli\Config;

use DeltaCli\Config\Database\DatabaseInterface;

class Config
{
    /**
     * @var DatabaseInterface[]
     */
    private $databases = [];

    /**
     * @var string
     */
    protected $browserUrl;

    public function addDatabase(DatabaseInterface $database)
    {
        $this->databases[] = $database;

        return $this;
    }

    /**
     * @return DatabaseInterface[]
     */
    public function getDatabases()
    {
        return $this->databases;
    }

    /**
     * @param string $browserUrl
     * @return $this
     */
    public function setBrowserUrl($browserUrl)
    {
        if (0 !== stripos($browserUrl, 'http:')) {
            $browserUrl = 'http://' . $browserUrl;
        }

        $this->browserUrl = $browserUrl;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasBrowserUrl()
    {
        return null !== $this->browserUrl;
    }

    /**
     * @return string
     */
    public function getBrowserUrl()
    {
        return $this->browserUrl;
    }
}
