<?php

namespace gihp;

class Repository {
    private $io;

    function __construct(\gihp\IO\IOInterface $io) {
        $this->io = $io;
    }
}
