<?php

namespace gihp\Object;

use gihp\IO\IOInterface;
use gihp\IO\WritableInterface;

/**
 * A casual blob object.
 *
 * Usually holds file data, but can contain anything you want.
 * @internal Store all strings that you want in here. Just a wrapper around
 * Internal, but with the right data-type
 */
class Blob extends Internal implements WritableInterface
{
    /**
     * Creates a new blob
     * @param string $data The data to associate with the blob
     */
    public function __construct($data)
    {
        parent::__construct($data);
    }

    /**
     * Gets the data in the blob
     */
    public function getData()
    {
        return parent::getData();
    }

    /**
     * Writes this blob to IO
     * @internal
     */
    public function write(IOInterface $io)
    {
        $io->addObject($this);
    }
}
