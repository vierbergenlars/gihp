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

use gihp\IO\IOInterface;
use gihp\IO\WritableInterface;

/**
 * A casual blob object.
 *
 * Usually holds file data, but can contain anything you want.
 * @internal Store all strings that you want in here. Just a wrapper around
 * Internal, but with the right data-type
 */
class Blob extends Internal implements WritableInterface
{
    /**
     * Creates a new blob
     * @param string $data The data to associate with the blob
     */
    public function __construct($data)
    {
        parent::__construct($data);
    }

    /**
     * Gets the data in the blob
     */
    public function getData()
    {
        return parent::getData();
    }

    /**
     * Writes this blob to IO
     * @internal
     */
    public function write(IOInterface $io)
    {
        $io->addObject($this);
    }
}
