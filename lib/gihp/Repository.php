<?php

namespace gihp;

class Repository
{
    private $io;

    public function __construct(\gihp\IO\IOInterface $io)
    {
        $this->io = $io;
    }
}
