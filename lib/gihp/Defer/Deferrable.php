<?php

namespace gihp\Defer;

/**
 * Base interface for all deferrable classes
 * @internal
 */
interface Deferrable {
    /**
     * Imports the given data through the loader
     * @param Loader $loader The loader used for the data
     * @param mixed $data The data to load
     * @return mixed
     */
    static function import(Loader $loader, $data);
}
