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
        //$this->jsonFile = ($jsonFile ?: $_SERVER['HOME'] . '/.delta-cli-cache.json');
        $this->jsonFile = ($jsonFile ?: '~/.delta-cli-cache.json');
    }

    public function store($key, $value)
    {
        $data = $this->getData();

        $data[$key] = $value;

        $this->writeData($data);

        return $this;
    }

    public function clear($key)
    {
        $data = $this->getData();

        if (isset($data[$key])) {
            unset($data[$key]);
        }

        $this->writeData($data);

        return $this;
    }

    public function fetch($key)
    {
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

    private function writeData(array $data)
    {
        file_put_contents($this->jsonFile, json_encode($data), LOCK_EX);
        $this->clearData();
    }

    private function getData()
    {
        if (!file_exists($this->jsonFile)) {
            return [];
        }

        if (null === $this->data) {
            $this->data = json_decode(
                file_get_contents($this->jsonFile),
                true
            );
        }

        return $this->data;
    }
}
