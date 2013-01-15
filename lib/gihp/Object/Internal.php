<?php

namespace gihp\Object;

use gihp\Defer\Deferrable;
use gihp\Defer\Loader as DLoader;

/**
 * Base class for all sha1-based objects
 *
 * Parses the basic git structure for these objects and verifies them
 */
class Internal implements Deferrable {
    /**
     * Data in the object
     * @var string
     */
    private $data;

    /**
     * Creates a new Internal object
     * @param string|null $data The data in the object
     * @internal
     */
    function __construct($data=null) {
        $this->data = $data;
    }

    /**
     * Overwrites the data in the object
     * @param string $data
     */
    protected function setData($data) {
        $this->data = $data;
    }

    /**
     * Appends data. Does not overwrite it.
     * @param string $data
     */
    protected function appendData($data) {
        $this->data.=$data;
    }

    /**
     * Gets the data stored in here
     *
     * Note: only the data, not the checksums and so around it.
     * @return string
     */
    protected function getData() {
        return $this->data;
    }
    
    private function __toString() {
        throw new \LogicException('Objects do no longer have a __toString() method.');
    }

    /**
     * Gets the SHA1 hash of the object
     * @return sting
     */
    function getSHA1() {
        return sha1(\gihp\Parser\File::exportObject($this));
    }
}
