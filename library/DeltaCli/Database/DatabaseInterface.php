<?php

namespace DeltaCli\Database;

interface DatabaseInterface
{
    public function exists($name);

    public function create($name);

    public function drop($name);
}
