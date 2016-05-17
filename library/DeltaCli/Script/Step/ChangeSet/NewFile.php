<?php

namespace DeltaCli\Script\Step\ChangeSet;

class NewFile implements ChangeInterface
{
    private $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function getSummaryTitle()
    {
        return 'New files';
    }

    public function render()
    {
        return sprintf('%-9s %s', 'New File', $this->file);
    }
}
