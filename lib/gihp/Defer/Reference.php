<?php

namespace gihp\Defer;

class Reference {
    private $ref;
    private $loader;
    function __construct(Loader $loader, $ref) {
        $this->ref = $ref;
        $this->loader = $loader;
    }

    function loadRef() {
        return $this->loader->load($this->ref);
    }
}
