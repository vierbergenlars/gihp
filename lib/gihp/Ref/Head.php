<?php

namespace gihp\Ref;

use gihp\Defer\Loader as DLoader;
use gihp\Defer\Object as Defer;
use gihp\Defer\Reference as DReference;

/**
 * Head reference
 *
 * Points to the tip of a branch
 */
class Head extends Reference {

    /**
     * Loads the head reference from raw data
     * @return Head
     * @internal
     */
    static function import(Dloader $loader, $data) {
        list($name, $ref) = explode("\0", $data);
        $ref = substr($ref, 0, 40);
        return Defer::defer(array('commit'=>new DReference($loader->getObjectLoader(), $ref), 'name'=>$name), __CLASS__);
    }
}
