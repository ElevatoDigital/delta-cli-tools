<?php

namespace DeltaCli\Script\Step\ChangeSet;

class Delete implements ChangeInterface
{
    private $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function render()
    {
        return sprintf('%-9s %s', 'Delete', $this->file);
    }

    public function getSummaryTitle()
    {
        return 'Deletes';
    }
}
