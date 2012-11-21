<?php

namespace gihp\IO;

/**
 * Indicates objects that can be written to the IO
 */
interface WritableInterface {
    /**
     * Write the object and its dependencies to io
     * @param IOInterface $io The IOInterface to write to
     */
    function write(IOInterface $io);
}
