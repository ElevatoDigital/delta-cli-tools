<?php

namespace DeltaCli;

class Cache
{
    /**
     * @var string
     */
    private $jsonFile;

    /**
     * @var array
     */
    private $data = null;

    public function __construct($jsonFile = null)
    {
        $this->jsonFile = ($jsonFile ?: $_SERVER['HOME'] . '/.delta-cli-cache.json');
    }

    public function store($key, $value)
    {
        $data = $this->getData();

        $data[$key] = $value;

        file_put_contents($this->jsonFile, json_encode($data), LOCK_EX);

        return $this;
    }

    public function fetch($key)
    {
        if (!file_exists($this->jsonFile)) {
            return false;
        }

        $data = $this->getData();

        if (isset($data[$key])) {
            return $data[$key];
        }

        return false;
    }

    private function clearData()
    {
        $this->data = null;

        return $this;
    }

    private function getData()
    {
        if (null === $this->data) {
            $this->data = json_decode(
                file_get_contents($this->jsonFile),
                true
            );
        }

        return $this->data;
    }
}
