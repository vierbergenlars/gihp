<?php

namespace gihp\Object;

use gihp\Defer\Deferrable;

/**
 * Base class for all sha1-based objects
 *
 */
abstract class Internal implements Deferrable
{
    /**
     * Data in the object
     * @var string
     */
    protected $data;

    /**
     * Object SHA1
     * @var string
     */
    protected $sha1;

    /**
     * Creates a new Internal object
     * @param string|null $data The data in the object
     * @internal
     */
    protected function __construct($data=null)
    {
        $this->data = $data;
    }

    /**
     * Overwrites the data in the object
     * @param string $data
     */
    protected function setData($data)
    {
        $this->sha1 = null;
        $this->data = $data;
    }

    /**
     * Appends data. Does not overwrite it.
     * @param string $data
     */
    protected function appendData($data)
    {
        $this->sha1 = null;
        $this->data.=$data;
    }

    /**
     * Gets the data stored in here
     *
     * Note: only the data, not the checksums and so around it.
     * @return string
     */
    protected function getData()
    {
        return $this->data;
    }

    /**
     * Clears the SHA1 hash, causing it to be recalculated when it is retrieved.
     * @internal
     */
    public function clearSHA1()
    {
        $this->sha1 = null;
    }

    /**
     * Gets the SHA1 hash of the object
     * @return sting
     */
    public function getSHA1()
    {
        if (!$this->sha1) {
            $this->sha1 = sha1(\gihp\Parser\File::exportObject($this));
        }

        return $this->sha1;
    }
}
