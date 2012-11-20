<?php

namespace gihp\Defer;

/**
 * Temporary holds a reference to the object
 * @internal
 */
class Reference
{
    /**
     * The reference identifier
     */
    private $ref;
    /**
     * The loader used to load the object data
     */
    private $loader;
    /**
     * Create a new reference
     * @param Loader $loader The loader to load the object data
     * @param mixed  $ref    A reference that uniquely identifies the object to the loader
     */
    public function __construct(Loader $loader, $ref)
    {
        $this->ref = $ref;
        $this->loader = $loader;
    }

    /**
     * Loads the reference through the loader
     */
    public function loadRef()
    {
        return $this->loader->load($this->ref);
    }
}
