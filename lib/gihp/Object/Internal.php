<?php
/**
 * Copyright (c) 2013 Lars Vierbergen
 * 
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

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
