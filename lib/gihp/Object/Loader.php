<?php

namespace gihp\Object;

use gihp\Defer\Loader as DLoader;
use gihp\IO\IOInterface;

/**
 * A loader for objects.
 * Always invokes load on Internal, but that will load the right object.
 * @internal
 */
class Loader implements DLoader
{
    /**
     * The IO class
     * @var IOInterface
     */
    private $io;

    /**
     * Creates a new loader
     * @param IOInterface $io An IO module
     */
    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    /**
     * Loads the object
     * @param  string   $sha1 The hash of the object to load
     * @return Internal
     */
    public function load($sha1)
    {
        return $this->io->readObject($sha1);
    }

}
