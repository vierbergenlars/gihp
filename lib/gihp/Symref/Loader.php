<?php

namespace gihp\Symref;

use gihp\Defer\Loader as DLoader;
use gihp\IO\IOInterface;

/**
 * A loader for symbolic references.
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
     * Loads the symbolic reference
     * @param null $null Not used.
     * @return SymbolicReference
     */
    function load($null=null) {
        return $this->io->readHEAD();
    }

    /**
     * Returns the reference loader
     * @return \gihp\Ref\Loader
     */
    function getReferenceLoader() {
        return new \gihp\Ref\Loader($this->io);
    }

}
