<?php

namespace gihp\Defer;

/**
 * Interface for an object loader
 * @internal
 */
interface Loader {
    /**
     * Loads the object
     * @param mixed $identifier An unique identifier for the object
     */
    function load($identifier);
}
