<?php

namespace gihp\Ref;

use gihp\Defer\Loader as DLoader;
use gihp\IO\IOInterface;

/**
 * A loader for references.
 * @internal
 */
class Loader implements DLoader {
    /**
     * The IO class
     * @var IOInterface
     */
    private $io;

    /**
     * Creates a new loader
     * @param IOInterface $io An IO module
     */
    function __construct(IOInterface $io) {
        $this->io = $io;
    }

    /**
     * Loads the reference
     * @param string $path The path to the reference
     * @return Internal
     */
    function load($sha1) {
        return $this->io->readObject($sha1);
    }

    /**
     * Returns the object loader
     * @return \gihp\Object\Loader
     */
    function getObjectLoader() {
        return new \gihp\Object\Loader($this->io);
    }

}
