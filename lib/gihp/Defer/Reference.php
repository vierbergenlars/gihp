<?php

namespace gihp\Defer;

class Reference
{
    private $ref;
    private $loader;
    public function __construct(Loader $loader, $ref)
    {
        $this->ref = $ref;
        $this->loader = $loader;
    }

    public function loadRef()
    {
        return $this->loader->load($this->ref);
    }
}
