<?php

namespace gihp\Ref;

use gihp\Defer\Loader as DLoader;
use gihp\Defer\Object as Defer;
use gihp\Defer\Reference as DReference;

/**
 * Tag reference
 *
 * Keeps a pointer to a commit that is easier to remember
 */
class Tag extends Reference {

    /**
     * Loads the tag reference from raw data
     */
    static function import(Dloader $loader, $data) {
        list($name, $ref) = explode("\0", $data);
        $ref = substr($ref, 0, 40);
        return Defer::defer(array('commit'=>new DReference($loader->getObjectLoader(), $ref), 'name'=>$name), __CLASS__);
    }
}
