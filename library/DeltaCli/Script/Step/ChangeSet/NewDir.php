<?php

namespace DeltaCli\Script\Step\ChangeSet;

class NewDir implements ChangeInterface
{
    private $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function render()
    {
        return sprintf('%-9s %s', 'New Dir', $this->file);
    }

    public function getSummaryTitle()
    {
        return 'New directories';
    }
}
