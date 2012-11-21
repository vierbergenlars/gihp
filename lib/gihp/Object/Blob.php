<?php

namespace gihp\Object;

use gihp\Defer\Loader as DLoader;

use gihp\IO\IOInterface;
use gihp\IO\WritableInterface;

/**
 * A casual blob object.
 *
 * Usually holds file data, but can contain anything you want.
 * @internal Store all strings that you want in here. Just a wrapper around
 * Internal, but with the right data-type
 */
class Blob extends Internal implements WritableInterface {
    function __construct($data) {
        parent::__construct(parent::BLOB, $data);
    }

    function write(IOInterface $io) {
        $io->addObject($this);
    }

    static function import(DLoader $loader, $data) {
        return new self($data);
    }
}
