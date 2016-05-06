<?php

namespace DeltaCli;

class FileTemplate
{
    /**
     * @var string
     */
    private $templateFile;

    /**
     * @var array
     */
    private $data = [];

    /**
     * FileTemplate constructor.
     * @param string $templateFile
     */
    public function __construct($templateFile)
    {
        $this->templateFile = $templateFile;
    }

    public function assign($name, $value)
    {
        $this->data[$name] = $value;

        return $this;
    }

    public function __get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    public function write($outputFile)
    {
        ob_start();
        require $this->templateFile;
        $output = ob_get_clean();

        file_put_contents($outputFile, $output, LOCK_EX);
    }
}
