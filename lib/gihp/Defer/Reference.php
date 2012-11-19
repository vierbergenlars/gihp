<?php

namespace gihp\Defer;

class Reference {
    private $ref;
    function __construct($ref) {
        $this->ref = $ref;
    }

    function loadRef(Loader $loader) {
        return $loader->load($this->ref);
    }
}
