<?php

namespace gihp\Defer;

class Reference
{
    private $ref;
    public function __construct($ref)
    {
        $this->ref = $ref;
    }

    public function loadRef(Loader $loader)
    {
        return $loader->load($this->ref);
    }
}
