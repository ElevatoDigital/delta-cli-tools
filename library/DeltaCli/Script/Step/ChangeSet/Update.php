<?php

namespace DeltaCli\Script\Step\ChangeSet;

class Update implements ChangeInterface
{
    private $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function render()
    {
        return sprintf('%-9s %s', 'Update', $this->file);
    }

    public function getSummaryTitle()
    {
        return 'Updates';
    }
}
